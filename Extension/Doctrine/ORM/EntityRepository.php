<?php

namespace AndreasGlaser\DCEventBundle\Extension\Doctrine\ORM;

use AndreasGlaser\DCEventBundle\EventListener\DCEventListener;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;

/**
 * Class EntityRepository
 *
 * @package AndreasGlaser\DCEventBundle\Extension\Doctrine\ORM
 * @inheritdoc
 * @author  Andreas Glaser
 */
class EntityRepository extends BaseEntityRepository
{
    /**
     * @var DCEventListener|null
     */
    protected $dcEventListener = null;

    /**
     * @inheritdoc
     */
    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    /**
     * @param \AndreasGlaser\DCEventBundle\EventListener\DCEventListener $dcEventListener
     *
     * @return $this
     * @author Andreas Glaser
     */
    public function bindDCEventListener(DCEventListener &$dcEventListener = null)
    {
        if ($dcEventListener) {
            $this->dcEventListener = $dcEventListener;
        }

        return $this;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function persist($entity)
    {
        if ($this->dcEventListener) {
            $this->dcEventListener->persist($entity);
        } else {
            $this->_em->persist($entity);
        }

        return $this;
    }

    /**
     * Recalculates entity change set if doctrine event listener has been bound
     *
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function recalculate($entity)
    {
        if ($this->dcEventListener) {
            $this->dcEventListener->recalculate($entity);
        }

        return $this;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function delete($entity)
    {
        if ($this->dcEventListener) {
            $this->dcEventListener->remove($entity);
        } else {
            $this->_em->remove($entity);
        }

        return $this;
    }
}