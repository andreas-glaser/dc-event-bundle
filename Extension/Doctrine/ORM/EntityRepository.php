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
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @author Andreas Glaser
     */
    protected function qb()
    {
        return $this->_em->createQueryBuilder();
    }

    /**
     * @param      $id
     * @param null $version
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @author Andreas Glaser
     */
    public function findAsArray($id, $version = null)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->select('entity')
            ->from($this->_entityName, 'entity')
            ->where($qb->expr()->eq('entity.id', ':id'))
            ->setParameter(':id', $id);

        if ($version) {
            $qb
                ->andWhere($qb->expr()->eq('entity.version', ':version'))
                ->setParameter(':version', $version);
        }

        return $qb
            ->getQuery()
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);
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