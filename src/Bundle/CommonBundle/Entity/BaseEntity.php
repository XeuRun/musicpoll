<?php
namespace Bundle\CommonBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BaseEntity
 */
class BaseEntity
{
    public function prePersist()
    {
        try {
            $currentTime = new \DateTime();
            $this->setCreatedAt($currentTime);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function preUpdate()
    {
        try {
            $currentTime = new \DateTime();
            $this->setUpdatedAt($currentTime);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updatedAt", type="datetime",nullable=true)
     */
    protected $updatedAt;

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Возвращает имя класса
     * @return string
     */
    protected function getEntityType()
    {
        return end(explode("\\", get_class($this)));
    }
}