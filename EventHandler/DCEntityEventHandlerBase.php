<?php

namespace AndreasGlaser\DCEventBundle\EventHandler;

use AndreasGlaser\DCEventBundle\EventHandler;
use AndreasGlaser\DCEventBundle\EventListener\DCEventListener;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DCEntityEventHandlerBase
 *
 * @author Andreas Glaser
 */
abstract class DCEntityEventHandlerBase extends ContainerAware
{
    /**
     * @var \AndreasGlaser\DCEventBundle\EventListener\DCEventListener
     */
    protected $dcEventListener;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @param \AndreasGlaser\DCEventBundle\EventListener\DCEventListener $customEventListener
     * @param \Doctrine\ORM\EntityManagerInterface                       $entityManager
     * @param                                                            $entity
     * @param \Symfony\Component\DependencyInjection\ContainerInterface  $container
     */
    public function __construct(DCEventListener $customEventListener, EntityManagerInterface $entityManager, $entity, ContainerInterface $container)
    {
        $this->dcEventListener = $customEventListener;
        $this->em = $entityManager;
        $this->entity = $entity;
        $this->setContainer($container);
    }

    /**
     * @param $entity
     *
     * @author Andreas Glaser
     */
    protected function persist($entity)
    {
        $this->dcEventListener->persist($entity);
    }

    /**
     * @param $entity
     *
     * @author Andreas Glaser
     */
    protected function recalculate($entity)
    {
        $this->dcEventListener->recalculate($entity);
    }

    /**
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

        if (method_exists($repo, 'bindDCEventListener')) {
            $repo->bindDCEventListener($this->dcEventListener);
        }

        return $repo;
    }

    /**
     * Sets an entity flag
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagSet($flagName, $entity)
    {
        return $this->dcEventListener->flagSet($flagName, $entity);
    }

    /**
     * Checks if an entity flag has been set
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagExists($flagName, $entity)
    {
        return $this->dcEventListener->flagExists($flagName, $entity);
    }

    /**
     * Removes entity flag
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagRemove($flagName, $entity)
    {
        return $this->dcEventListener->flagRemove($flagName, $entity);
    }

    /**
     * Checks if an entity flag exists and removes it.
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagExistsAndRemove($flagName, $entity)
    {
        return $this->dcEventListener->flagExistsAndRemove($flagName, $entity);
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