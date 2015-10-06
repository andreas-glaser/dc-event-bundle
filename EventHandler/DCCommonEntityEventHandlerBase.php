<?php

namespace AndreasGlaser\DCEventBundle\EventHandler;

use AndreasGlaser\DCEventBundle\EventHandler;
use AndreasGlaser\DCEventBundle\EventListener\DCEventListener;
use AndreasGlaser\DCEventBundle\EventListener\DCEventListenerAwareTrait;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use Doctrine\ORM;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DCCommonEntityEventHandlerBase
 *
 * @package AndreasGlaser\DCEventBundle\EventHandler
 * @author  Andreas Glaser
 */
abstract class DCCommonEntityEventHandlerBase extends ContainerAware
{
    use DCEventListenerAwareTrait;

    /**
     * @var ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface  $container
     * @param \AndreasGlaser\DCEventBundle\EventListener\DCEventListener $dcEventListener
     * @param \Doctrine\ORM\EntityManagerInterface                       $entityManager
     */
    public function __construct(ContainerInterface $container, DCEventListener $dcEventListener, ORM\EntityManagerInterface $entityManager)
    {
        $this->setContainer($container);
        $this->setDCEventListener($dcEventListener);
        $this->dcEventListener = $dcEventListener;
        $this->entityManager = $entityManager;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function persist($entity)
    {
        $this->dcEventListener->persist($entity);

        return $this;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function recalculate($entity)
    {
        $this->dcEventListener->recalculate($entity);

        return $this;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function remove($entity)
    {
        $this->dcEventListener->remove($entity);

        return $this;
    }

    /**
     * @param $entity
     *
     * @internal
     *
     * @author Andreas Glaser
     */
    public function initPrePersist($entity)
    {
        $this->prePersist($entity);

        $method = 'prePersist' . $this->getEntityName($entity);

        if (method_exists($this, $method)) {
            $this->$method($entity);
        }
    }

    /**
     * @param                                                     $entity
     * @param \AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper $changeSetHelper
     *
     * @internal
     *
     * @author Andreas Glaser
     */
    public function initPreUpdate($entity, ChangeSetHelper $changeSetHelper)
    {
        $this->preUpdate($entity, $changeSetHelper);

        $method = 'preUpdate' . $this->getEntityName($entity);
        if (method_exists($this, $method)) {
            $this->$method($entity, $changeSetHelper);
        }
    }

    /**
     * @param $entity
     *
     * @internal
     *
     * @author Andreas Glaser
     */
    public function initPreRemove($entity)
    {
        $this->preRemove($entity);

        $method = 'preRemove' . $this->getEntityName($entity);
        if (method_exists($this, $method)) {
            $this->$method($entity);
        }
    }

    /**
     * @param $entity
     *
     * @internal
     *
     * @author Andreas Glaser
     */
    public function initPostPersist($entity)
    {
        $this->postPersist($entity);

        $method = 'postPersist' . $this->getEntityName($entity);
        if (method_exists($this, $method)) {
            $this->$method($entity);
        }
    }

    /**
     * @param $entity
     *
     * @internal
     *
     * @author Andreas Glaser
     */
    public function initPostUpdate($entity)
    {
        $this->postUpdate($entity);

        $method = 'postUpdate' . $this->getEntityName($entity);
        if (method_exists($this, $method)) {
            $this->$method($entity);
        }
    }

    /**
     * @param $entity
     *
     * @internal
     *
     * @author Andreas Glaser
     */
    public function initPostRemove($entity)
    {
        $this->postRemove($entity);

        $method = 'postRemove' . $this->getEntityName($entity);
        if (method_exists($this, $method)) {
            $this->$method($entity);
        }
    }

    /**
     * @param $entity
     *
     * @return string
     * @author Andreas Glaser
     */
    private function getEntityName($entity)
    {
        $class = get_class($entity);

        return substr($class, strrpos($class, '\\') + 1);
    }

    /**
     * Returns an entity repository with bound event listener.
     *
     * @param $repositoryName
     *
     * @return EntityRepository
     * @author Andreas Glaser
     */
    public function getRepository($repositoryName)
    {
        $repo = $this->entityManager->getRepository($repositoryName);

        if (method_exists($repo, 'setDCEventListener')) {
            $repo->setDCEventListener($this);
        }

        return $repo;
    }

    /**
     * @param $entity
     *
     * @return void
     * @author Andreas Glaser
     */
    abstract public function prePersist($entity);

    /**
     * @param                                                     $entity
     * @param \AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper $changeSetHelper
     *
     * @return void
     * @author Andreas Glaser
     */
    abstract public function preUpdate($entity, ChangeSetHelper $changeSetHelper);

    /**
     * @param $entity
     *
     * @return void
     * @author Andreas Glaser
     */
    abstract public function preRemove($entity);

    /**
     * @param $entity
     *
     * @return void
     * @author Andreas Glaser
     */
    abstract public function postPersist($entity);

    /**
     * @param $entity
     *
     * @return void
     * @author Andreas Glaser
     */
    abstract public function postUpdate($entity);

    /**
     * @param $entity
     *
     * @return void
     * @author Andreas Glaser
     */
    abstract public function postRemove($entity);
}