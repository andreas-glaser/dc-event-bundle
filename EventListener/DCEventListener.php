<?php

namespace AndreasGlaser\DCEventBundle\EventListener;

use AndreasGlaser\DCEventBundle\EventHandler\Annotations\DCEntityEventHandlerReader;
use AndreasGlaser\DCEventBundle\EventHandler\DCEntityEventHandlerBase;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
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

    protected $commonEntityEventHandler;

    /**
     * @var array
     */
    protected $processedEntities = [
        'persist' => [],
        'update'  => [],
        'remove'  => []
    ];

    protected $config = [];

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
        $this->config = $this->container->getParameter('andreas_glaser_dc_event');
    }

    /**
     * @inheritdoc
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
        return ['preFlush', 'postFlush'];
    }

    /**
     * @param \Doctrine\ORM\Event\PreFlushEventArgs $eventArgs
     *
     * @author Andreas Glaser
     */
    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $this->entityManager = $eventArgs->getEntityManager();
        $this->unitOfWork = $this->entityManager->getUnitOfWork();

        if ($this->config['common_entity_event_handler']) {
            if (class_exists($this->config['common_entity_event_handler'])) {
                $this->commonEntityEventHandler = new $this->config['common_entity_event_handler']($this->container, $this, $this->entityManager);
            } else {
                throw new \InvalidArgumentException(strtr('":class" does not exist', [':class' => $this->config['common_entity_event_handler']]));
            }
        }

        $this->unitOfWork->computeChangeSets();
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
        foreach ($this->processedEntities['persist'] AS $entity) {

            if ($this->commonEntityEventHandler) {
                $this->commonEntityEventHandler->initPostPersist($entity);
            }

            if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
                $entityEventHandler->postPersist();
            }
        }

        foreach ($this->processedEntities['update'] AS $entity) {

            if ($this->commonEntityEventHandler) {
                $this->commonEntityEventHandler->initPostUpdate($entity);
            }

            if ($entityEventHandler = $this->getEntityEventHandler($entity)) {
                $entityEventHandler->postUpdate();
            }
        }

        foreach ($this->processedEntities['remove'] AS $entity) {

            if ($this->commonEntityEventHandler) {
                $this->commonEntityEventHandler->initPostRemove($entity);
            }

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
            $this->processRecalculationQueue();
            $reRun = true;
        }

        foreach ($this->unitOfWork->getScheduledEntityUpdates() + $this->unitOfWork->getScheduledCollectionUpdates() AS $hash => $entity) {
            if (array_key_exists($hash, $this->processedEntities['update'])) {
                continue;
            }
            $this->initUpdate($entity, true);
            $this->processRecalculationQueue();
            $reRun = true;
        }

        foreach ($this->unitOfWork->getScheduledEntityDeletions() + $this->unitOfWork->getScheduledCollectionDeletions() AS $hash => $entity) {
            if (array_key_exists($hash, $this->processedEntities['remove'])) {
                continue;
            }
            $this->initRemove($entity, true);
            $this->processRecalculationQueue();
            $reRun = true;
        }

        return $reRun;
    }

    /**
     * Returns entity event handler for given entity if exists.
     *
     * @param $entity
     *
     * @return DCEntityEventHandlerBase|bool
     * @throws \Exception
     * @author Andreas Glaser
     */
    protected function getEntityEventHandler($entity)
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

        if ($this->commonEntityEventHandler) {
            $this->commonEntityEventHandler->initPrePersist($entity);
            $this->computeChangeSet($entity);
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
            $this->computeChangeSet($entity);
        }

        if ($this->commonEntityEventHandler) {
            $this->commonEntityEventHandler->initPreUpdate($entity, ChangeSetHelper::factory($this->unitOfWork->getEntityChangeSet($entity)));
            $this->computeChangeSet($entity);
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

        if ($this->commonEntityEventHandler) {
            $this->commonEntityEventHandler->initPreRemove($entity);
            $this->computeChangeSet($entity);
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
     * Computes or re-computes changes of given entity.
     *
     * @param $entity
     *
     * @author Andreas Glaser
     */
    protected function computeChangeSet(&$entity)
    {
        if ($this->unitOfWork->getEntityChangeSet($entity)) {
            $this->unitOfWork->recomputeSingleEntityChangeSet($this->entityManager->getClassMetadata(get_class($entity)), $entity);
        } else {
            $this->unitOfWork->computeChangeSet($this->entityManager->getClassMetadata(get_class($entity)), $entity);
        }
    }

    /**
     * This function should be used to persist new entities within the persist, update and remove methods
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
     * This function needs to be applied to entities that are changed as part of the persist, update and remove methods
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
     * This function needs to be applied to entities that are removed as part of the persist, update and remove methods
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
        if (!is_object($entity)) {
            throw new \RuntimeException('Flag requires an entity to be set');
        }

        if ($this->flagExists($flagName, $entity)) {
            return false;
        }

        $hash = spl_object_hash($entity);

        if (!is_array($this->flags[$hash])) {
            $this->flags[$hash] = [];
        }

        $this->flags[$hash][$flagName] = $flagName;

        return true;
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
        if (!is_object($entity)) {
            throw new \RuntimeException('Flag requires an entity to be set');
        }

        $hash = spl_object_hash($entity);

        return array_key_exists($hash, $this->flags) && array_key_exists($flagName, $this->flags[$hash]);
    }

    /**
     * Removes an entity flag
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagRemove($flagName, $entity)
    {
        if ($this->flagExists($flagName, $entity)) {
            $hash = spl_object_hash($entity);
            unset($this->flags[$hash][$flagName]);

            return true;
        }

        return false;
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
        $exists = $this->flagExists($flagName, $entity);
        $this->flagRemove($flagName, $entity);

        return $exists;
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

        if (method_exists($repo, 'bindDCEventListener')) {
            $repo->bindDCEventListener($this);
        }

        return $repo;
    }
}