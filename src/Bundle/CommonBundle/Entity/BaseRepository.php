<?php

namespace Bundle\CommonBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * BaseRepository
 */
class BaseRepository extends EntityRepository
{
    /** @var Container */
    private $container;
    /** @var \Bundle\CommonBundle\Entity\User */
    protected $currentUser;

    /**
     * @param $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Устанавливает авторизованного пользователя в св-во currentUser из SecurityContext
     * @param SecurityContext $securityContext
     */
    public function setSecurityContext(SecurityContext $securityContext)
    {
        if ($token = $securityContext->getToken()) {
            if ($token->getUser() instanceof User) {
                $this->currentUser = $token->getUser();
            }
        }
    }

    /**
     * @return mixed
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    /**
     * Начинает транзакцию
     */
    public function start()
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
    }

    /**
     * Завершает транзакцию
     */
    public function commit()
    {
        $em = $this->getEntityManager();
        $em->getConnection()->commit();
    }

    /**
     * Откатывает транзакцию
     */
    public function rollback()
    {
        $em = $this->getEntityManager();
        $em->getConnection()->rollback();
        $em->close();
    }

    /** Событие до вставки сущности */
    protected function prePersist(BaseEntity $entity) {
        /** Устанавливает автора для сущности */
        if (method_exists($entity, 'setAuthor') && $this->getCurrentUser()) {
            $entity->setAuthor($this->getCurrentUser());
        }
    }

    /**
     * Сохранение сущности
     * @param $entity
     * @throws \Doctrine\ORM\ORMException
     */
    public function save($entity)
    {
        $this->start();
        try {
            $em = $this->getEntityManager();
            $isNew = is_null($entity->getId());


            if ($isNew) {
                $this->prePersist($entity);
                $entity->prePersist();
                $em->persist($entity);
            } else {
                $entity->preUpdate();
            }
            $em->flush();

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw new ORMException($e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Удаление сущности
     * @param $entity
     * @throws \Doctrine\ORM\ORMException
     */
    public function remove($entity)
    {
        $this->start();
        try {
            $em = $this->getEntityManager();

            $em->remove($entity);
            $em->flush();

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw new ORMException('Не удается удалить объект ' . get_class($entity), 0, $e);
        }
    }

    /**
     * Обновление сущности
     * @param \Bundle\CommonBundle\Entity\BaseEntity $entity
     */
    public function refresh(BaseEntity $entity)
    {
        $this->getEntityManager()->refresh($entity);
    }

    /**
     * Вернуть результат из базы
     * @param $sql
     * @return array
     */
    public function sqlQuery($sql, $single=false)
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();

        if (!empty($results)) {
            if ($single) {
                $results = array_shift($results);
            }
        }

        return $results;
    }

    /**
     * Индексирует коллекцию ключами сущностей
     * 
     * @param array||collection $entities
     * @return array
     */
    public function indexEntities($entities)
    {
        $array = array();
        foreach ($entities as $entity) {
            $array[$entity->getId()] = $entity;
        }

        return $array;
    }

    /**
     *
     * @param array $mapping
     * @param array $search
     * @return QueryBuilder
     */
    protected function _getQuery(&$mapping, $search = array())
    {
        $query = $this->createQueryBuilder('e');
        $this->_getQueryJoin($query, $mapping);
        $this->_getQuerySearch($query, $mapping, $search);

        return $query;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param array $mapping
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function _getQueryJoin(QueryBuilder &$query, $mapping)
    {
        /** Обязательные джойны */
        if (isset($mapping['innerJoin'])) {
            foreach ($mapping['innerJoin'] as $key => $value) {
                $query->innerJoin($value, $key);
            }
        }
        /** Левые джойны */
        if (isset($mapping['leftJoin'])) {
            foreach ($mapping['leftJoin'] as $key => $value) {
                $query->leftJoin($value, $key);
            }
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param array $mapping
     * @param array $search
     * @return type
     */
    protected function _getQuerySearch(QueryBuilder &$query, $mapping, array $search)
    {
        if (empty($search)) {
            return;
        }

        list($key, $type, $text) = $search;
        $dbKey = $mapping['select'][$key];
        switch ($type) {
            case 'eq':
                $query->where($query->expr()->eq($dbKey, '?1'));
                $query->setParameter(1, $text);
                break;

            case 'likeStart':
                $query->where($query->expr()->like($dbKey, '?1'));
                $query->setParameter(1, sprintf('%s%%', $text));
                break;

            default:break;
        }
    }
}
