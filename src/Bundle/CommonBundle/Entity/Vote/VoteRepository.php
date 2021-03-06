<?php

namespace Bundle\CommonBundle\Entity\Vote;

use Bundle\CommonBundle\Entity\BaseEntity;
use Bundle\CommonBundle\Entity\BaseRepository;

/**
 * VoteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VoteRepository extends BaseRepository
{
    /** Событие после вставки сущности
     * @param BaseEntity $entity
     */
    protected function prePersist(BaseEntity $entity)
    {
        parent::prePersist($entity);

        if($entity->isDislike()) {
            $entity->getAuthor()->incrementDislikeSendCount();
            $entity->getSong()->decrementCounter();
            $entity->getSong()->getAuthor()->incrementDislikeReceiveCount();
        } else {
            $entity->getAuthor()->incrementLikeSendCount();
            $entity->getSong()->incrementCounter();
            $entity->getSong()->getAuthor()->incrementLikeReceiveCount();
        }
    }
}
