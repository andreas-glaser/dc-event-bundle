<?php

namespace AndreasGlaser\DCEventBundle\Extras;

use AndreasGlaser\DCEventBundle\EventListener\DCEventListenerAwareInterface;
use AndreasGlaser\DCEventBundle\EventListener\DCEventListenerAwareTrait;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Doctrine\ORM\Query;

/**
 * DCEventListener aware entity repository.
 *
 * @package AndreasGlaser\DCEventBundle\Extras
 * @inheritdoc
 * @author  Andreas Glaser
 */
class EntityRepository extends BaseEntityRepository implements DCEventListenerAwareInterface
{
    use DCEventListenerAwareTrait;

    /**
     * Persists entity.
     *
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function persist($entity)
    {
        if ($this->hasDCEventListener()) {
            $this->dcEventListenerPersist($entity);
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
        if ($this->hasDCEventListener()) {
            $this->dcEventListenerRecalculate($entity);
        }

        return $this;
    }

    /**
     * Removes entity.
     *
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function remove($entity)
    {
        if ($this->hasDCEventListener()) {
            $this->dcEventListenerRemove($entity);
        } else {
            $this->_em->remove($entity);
        }

        return $this;
    }

    /**
     * Returns entity repository with bound DCEventListener if available.
     *
     * @param $repositoryName
     *
     * @return EntityRepository
     * @author Andreas Glaser
     */
    protected function getRepository($repositoryName)
    {
        $repo = $this->_em->getRepository($repositoryName);

        if ($this->hasDCEventListener() && method_exists($repo, 'setDCEventListener')) {
            $repo->setDCEventListener($this->dcEventListener);
        }

        return $repo;
    }
}