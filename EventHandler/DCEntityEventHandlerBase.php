<?php

namespace AndreasGlaser\DCEventBundle\EventHandler;

use AndreasGlaser\DCEventBundle\EventHandler;
use AndreasGlaser\DCEventBundle\EventListener\DCEventListener;
use AndreasGlaser\DCEventBundle\EventListener\DCEventListenerAwareTrait;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use AndreasGlaser\Helpers\StringHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DCEntityEventHandlerBase
 *
 * @package AndreasGlaser\DCEventBundle\EventHandler
 * @author  Andreas Glaser
 */
abstract class DCEntityEventHandlerBase extends ContainerAware
{
    use DCEventListenerAwareTrait;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @param \AndreasGlaser\DCEventBundle\EventListener\DCEventListener $dcEventListener
     * @param \Doctrine\ORM\EntityManagerInterface                       $entityManager
     * @param                                                            $entity
     * @param \Symfony\Component\DependencyInjection\ContainerInterface  $container
     */
    public function __construct(DCEventListener $dcEventListener, EntityManagerInterface $entityManager, $entity, ContainerInterface $container)
    {
        $this->entity = $entity;

        if (!$this->supports()) {
            throw new DCEntityEventHandlerException('Entity event handler does not support this entity');
        }

        $this->setDCEventListener($dcEventListener);
        $this->em = $entityManager;
        $this->setContainer($container);
    }

    /**
     * @return bool
     * @author Andreas Glaser
     */
    public function supports()
    {
        $eehClassFullyQualified = get_called_class();

        if (!StringHelper::endsWith($eehClassFullyQualified, 'EEH')) {
            return false;
        }

        $eehClass = substr($eehClassFullyQualified, strrpos($eehClassFullyQualified, '\\') + 1, -3);
        $entityClassFullyQualified = get_class($this->entity);
        $entityClass = substr($entityClassFullyQualified, strrpos($entityClassFullyQualified, '\\') + 1);

        return $eehClass === $entityClass;
    }

    /**
     * Alias
     *
     * @param $entity
     *
     * @author Andreas Glaser
     */
    protected function persist($entity)
    {
        $this->dcEventListener->persist($entity);
    }

    /**
     * Alias
     *
     * @param $entity
     *
     * @author Andreas Glaser
     */
    protected function recalculate($entity)
    {
        $this->dcEventListener->recalculate($entity);
    }

    /**
     * Alias
     *
     * @param $entity
     *
     * @author Andreas Glaser
     */
    protected function remove($entity)
    {
        $this->dcEventListener->remove($entity);
    }

    /**
     * @param $repositoryName
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     * @author Andreas Glaser
     */
    protected function getRepository($repositoryName)
    {
        $repo = $this->em->getRepository($repositoryName);

        if (method_exists($repo, 'setDCEventListener')) {
            $repo->setDCEventListener($this->dcEventListener);
        }

        return $repo;
    }

    /**
     * @return void
     * @author Andreas Glaser
     */
    abstract public function prePersist();

    /**
     * @return void
     * @author Andreas Glaser
     */
    abstract public function postPersist();

    /**
     * @param \AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper $changeSet
     *
     * @return void
     * @author Andreas Glaser
     */
    abstract public function preUpdate(ChangeSetHelper $changeSet);

    /**
     * @return void
     * @author Andreas Glaser
     */
    abstract public function postUpdate();

    /**
     * @return void
     * @author Andreas Glaser
     */
    abstract public function preRemove();

    /**
     * @return void
     * @author Andreas Glaser
     */
    abstract public function postRemove();
}