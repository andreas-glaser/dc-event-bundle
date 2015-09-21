<?php

namespace AndreasGlaser\DCEventBundle\EventListener;

use AndreasGlaser\DCEventBundle\EntityEventHandler\Annotations\DCEntityEventHandlerReader;
use AndreasGlaser\DCEventBundle\EntityEventHandler\DCEntityEventHandlerBase;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class DCEventListener
 *
 * @package AndreasGlaser\DCEventBundle\EventListener
 * @author  Andreas Glaser
 */
class DCEventListener implements EventSubscriber, ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $flags = [];

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var UnitOfWork
     */
    protected $unitOfWork;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $recalculationQueue = [];

    /**
     * @var array
     */
    protected $entityEventHandlerCache = [];

    /**
     * @var array
     */
    protected $processedEntities = [
        'persist' => [],
        'update'  => [],
        'remove'  => []
    ];

    /**
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     * @param \Symfony\Component\DependencyInjection\ContainerInterface                           $container
     */
    public function __construct(TokenStorageInterface $tokenStorage, ContainerInterface $container)
    {
        $this->tokenStorage = $tokenStorage;
        $this->setContainer($container);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @author Andreas Glaser
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getSubscribedEvents()
    {
        return ['onFlush', 'postFlush'];
    }

    /**
     * @param \Doctrine\ORM\Event\OnFlushEventArgs $eventArgs
     *
     * @author Andreas Glaser
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->entityManager = $eventArgs->getEntityManager();
        $this->unitOfWork = $this->entityManager->getUnitOfWork();
        $max = 50;
        $current = 0;

        do {
            $runAgain = $this->executeEvents();
            $current++;
        } while ($runAgain === true && $current <= $max);

        if ($current >= $max) {
            throw new \RuntimeException('Too many iterations... something must have gone wrong.');
        }
    }

    /**
     * @param \Doctrine\ORM\Event\PostFlushEventArgs $postFlushEventArgs
     *
     * @author Andreas Glaser
     */
    public function postFlush(PostFlushEventArgs $postFlushEventArgs)
    {
        $this->unitOfWork->clear();

        foreach ($this->processedEntities['persist'] AS $entity) {
            if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
                $entityEventHandler->postPersist();
            }
        }

        foreach ($this->processedEntities['update'] AS $entity) {
            if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
                $entityEventHandler->postUpdate();
            }
        }

        foreach ($this->processedEntities['remove'] AS $entity) {
            if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
                $entityEventHandler->postRemove();
            }
        }
    }

    /**
     * @internal
     * @return bool
     * @author Andreas Glaser
     */
    protected function executeEvents()
    {
        $reRun = false;

        foreach ($this->unitOfWork->getScheduledEntityInsertions() AS $hash => $entity) {
            if (array_key_exists($hash, $this->processedEntities['persist'])) {
                continue;
            }
            $this->initPersist($entity, true);
            $reRun = true;
        }

        foreach ($this->unitOfWork->getScheduledEntityDeletions() AS $hash => $entity) {
            if (array_key_exists($hash, $this->processedEntities['remove'])) {
                continue;
            }
            $this->initRemove($entity, true);
            $reRun = true;
        }

        foreach ($this->unitOfWork->getScheduledEntityUpdates() AS $hash => $entity) {
            if (array_key_exists($hash, $this->processedEntities['update'])) {
                continue;
            }
            $this->initUpdate($entity, true);
            $reRun = true;
        }

        if ($this->processRecalculationQueue()) {
            $reRun = true;
        }

        return $reRun;
    }

    /**
     * @param $entity
     *
     * @return bool|DCEntityEventHandlerBase
     * @throws \Exception
     * @author Andreas Glaser
     */
    public function getEntityEventHandler($entity)
    {
        $entityHash = spl_object_hash($entity);

        if (array_key_exists($entityHash, $this->entityEventHandlerCache)) {
            return $this->entityEventHandlerCache[$entityHash];
        }

        if (!$eventHandlerClassName = DCEntityEventHandlerReader::get($entity)) {
            return false;
        }

        return $this->entityEventHandlerCache[$entityHash] = new $eventHandlerClassName($this, $this->entityManager, $entity, $this->container);
    }

    /**
     * @param      $entity
     * @param bool $isInitial
     *
     * @author Andreas Glaser
     */
    protected function initPersist(&$entity, $isInitial = false)
    {
        if (!$isInitial) {
            $this->entityManager->persist($entity);
        }

        if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
            $entityEventHandler->prePersist();
            $this->computeChangeSet($entity);
        }

        $this->processedEntities['persist'][spl_object_hash($entity)] = $entity;
    }

    /**
     * @param      $entity
     * @param bool $isInitial
     *
     * @author Andreas Glaser
     */
    protected function initUpdate(&$entity, $isInitial = false)
    {
        if (!$isInitial) {
            $this->recalculate($entity);
        }

        if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
            $entityEventHandler->preUpdate(ChangeSetHelper::factory($this->unitOfWork->getEntityChangeSet($entity)));
            $this->computeChangeSet($entity);
        }

        $this->processedEntities['update'][spl_object_hash($entity)] = $entity;
    }

    /**
     * @param      $entity
     * @param bool $isInitial
     *
     * @author Andreas Glaser
     */
    protected function initRemove(&$entity, $isInitial = false)
    {
        if (!$isInitial) {
            $this->entityManager->remove($entity);
        }

        if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
            $entityEventHandler->preRemove();
            $this->computeChangeSet($entity);
        }

        $this->processedEntities['remove'][spl_object_hash($entity)] = $entity;
    }

    /**
     * @internal
     * @return int
     * @author Andreas Glaser
     */
    protected function processRecalculationQueue()
    {
        $count = 0;
        foreach ($this->recalculationQueue AS $hash => $entity) {
            $this->initUpdate($entity, false);
            unset($this->recalculationQueue[$hash]);
            $count++;
        }

        return $count;
    }

    /**
     * This function should be used to persist new entities within the persist, update, remove methods
     *
     * @param $entity
     *
     * @author Andreas Glaser
     */
    public function persist(&$entity)
    {
        if (!$entity) {
            return;
        }

        $this->initPersist($entity, false);
    }

    /**
     * This function needs to be applied to entities that are changed as part of the persist, update, remove methods
     *
     * @param $entity
     *
     * @author Andreas Glaser
     */
    public function recalculate(&$entity)
    {
        if (!$entity) {
            return;
        }

        $hash = spl_object_hash($entity);
        if (!array_key_exists($hash, $this->recalculationQueue)) {
            $this->recalculationQueue[$hash] = $entity;
        }
    }

    /**
     * This function needs to be applied to entities that are removed as part of the persist, update, remove methods
     *
     * @param $entity
     *
     * @author Andreas Glaser
     */
    public function remove(&$entity)
    {
        if (!$entity) {
            return;
        }

        $this->initRemove($entity, false);
    }

    /**
     * @param $entity
     *
     * @author Andreas Glaser
     */
    public function computeChangeSet(&$entity)
    {
        if ($this->unitOfWork->getEntityChangeSet($entity)) {
            $this->unitOfWork->recomputeSingleEntityChangeSet($this->entityManager->getClassMetadata(get_class($entity)), $entity);
        } else {
            $this->unitOfWork->computeChangeSet($this->entityManager->getClassMetadata(get_class($entity)), $entity);
        }
    }

    /**
     * Sets a flag.
     *
     * @param             $flagName
     * @param null|object $entity
     *
     * @return $this
     *
     * @author Andreas Glaser
     */
    protected function flagSet($flagName, $entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException('Flag requires an entity to be set');
        }

        if (!method_exists($entity, 'getId')) {
            throw new \RuntimeException('Required method does not exist');
        }

        $class = get_class($entity);
        $id = $entity->getId();

        if (!array_key_exists($flagName, $this->flags)) {
            $this->flags[$flagName] = [];
        }

        $index = count($this->flags[$flagName]);
        $this->flags[$flagName][$index]['class'] = $class;
        $this->flags[$flagName][$index]['id'] = $id;

        return $this;
    }

    /**
     * Checks if a flag exists.
     *
     * @param $flagName
     *
     * @return bool
     *
     * @author Andreas Glaser
     */
    protected function flagExists($flagName, $entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException('Flag requires an entity to be set');
        }

        if (!method_exists($entity, 'getId')) {
            throw new \RuntimeException('Required method does not exist');
        }

        if (!array_key_exists($flagName, $this->flags)) {
            return false;
        }

        $class = get_class($entity);
        $id = $entity->getId();

        foreach ($this->flags[$flagName] AS $k => $entityData) {
            if ($class === $entityData['class'] && $id === $entityData['id']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $flagName
     * @param $entity
     *
     * @return bool
     *
     * @author Andreas Glaser
     */
    protected function flagRemove($flagName, $entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException('Flag requires an entity to be set');
        }

        if (!method_exists($entity, 'getId')) {
            throw new \RuntimeException('Required method does not exist');
        }

        if (!array_key_exists($flagName, $this->flags)) {
            return false;
        }

        $class = get_class($entity);
        $id = $entity->getId();

        foreach ($this->flags[$flagName] AS $k => $entityData) {
            if ($class === $entityData['class'] && $id === $entityData['id']) {
                unset($this->flags[$flagName][$k]);

                return true;
            }
        }

        return false;
    }

    /**
     * @param $flagName
     * @param $entity
     *
     * @return bool
     *
     * @author Andreas Glaser
     */
    protected function flagExistsAndRemove($flagName, $entity)
    {
        $exists = $this->flagExists($flagName, $entity);
        $this->flagRemove($flagName, $entity);

        return $exists;
    }

    /**
     * Returns entity repository with bound event listener.
     *
     * @param $repositoryName
     *
     * @return mixed
     * @author Andreas Glaser
     */
    protected function getRepository($repositoryName)
    {
        $repo = $this->entityManager->getRepository($repositoryName);

        if (method_exists($repo, 'bindDCEventListener')) {
            $repo->bindDCEventListener($this);
        }

        return $repo;
    }
}