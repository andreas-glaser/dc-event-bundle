<?php

namespace AndreasGlaser\DCEventBundle\EventListener;

use AndreasGlaser\DCEventBundle\EventHandler\Annotations\DCEntityEventHandlerReader;
use AndreasGlaser\DCEventBundle\EventHandler\DCCommonEntityEventHandlerBase;
use AndreasGlaser\DCEventBundle\EventHandler\DCEntityEventHandlerBase;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use AndreasGlaser\DCEventBundle\Helper\FlagHelper;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DCEventListener
 *
 * @package AndreasGlaser\DCEventBundle\EventListener
 * @author  Andreas Glaser
 */
class DCEventListener implements EventSubscriber
{
    use ContainerAwareTrait;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var UnitOfWork
     */
    protected $unitOfWork;

    /**
     * @var array
     */
    protected $recalculationQueue = [];

    /**
     * @var array
     */
    protected $entityEventHandlerCache = [];

    /**
     * @var DCCommonEntityEventHandlerBase
     */
    protected $commonEntityEventHandler;

    /**
     * @var array
     */
    protected $processedEntities = [
        'persist' => [],
        'update'  => [],
        'remove'  => [],
    ];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var \AndreasGlaser\DCEventBundle\Helper\FlagHelper
     */
    protected $flagHelper;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
        $this->config = $this->container->getParameter('andreas_glaser_dc_event');
        $this->flagHelper = FlagHelper::factory();
    }

    /**
     * Resets temporary values
     *
     * @author Andreas Glaser
     */
    protected function reset()
    {
        $this->recalculationQueue = [];
        $this->entityEventHandlerCache = [];
        $this->commonEntityEventHandler = null;
        $this->processedEntities = [
            'persist' => [],
            'update'  => [],
            'remove'  => [],
        ];
        $this->flagHelper = FlagHelper::factory();
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
        $this->reset();

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

        do {
            $runAgain = $this->executeEvents();
        } while ($runAgain === true);
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

        foreach ($this->unitOfWork->getScheduledEntityUpdates() AS $hash => $entity) {
            if (array_key_exists($hash, $this->processedEntities['update']) || array_key_exists($hash, $this->processedEntities['remove'])) {
                continue;
            }
            $this->initUpdate($entity, true);
            $this->processRecalculationQueue();
            $reRun = true;
        }

        foreach ($this->unitOfWork->getScheduledEntityDeletions() AS $hash => $entity) {
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
    protected function initPersist($entity, $isInitial = false)
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
    protected function initUpdate($entity, $isInitial = false)
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
    protected function initRemove($entity, $isInitial = false)
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
     * @return int
     * @author Andreas Glaser
     */
    protected function processRecalculationQueue()
    {
        $count = 0;
        foreach ($this->recalculationQueue AS $hash => $entity) {
            $this->computeChangeSet($entity);
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
    protected function computeChangeSet($entity)
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
    public function persist($entity)
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
    public function recalculate($entity)
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
    public function remove($entity)
    {
        if (!$entity) {
            return;
        }

        $this->initRemove($entity, false);
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
     * Exposes flag helper.
     *
     * @return \AndreasGlaser\DCEventBundle\Helper\FlagHelper
     * @author Andreas Glaser
     */
    public function getFlagHelper()
    {
        return $this->flagHelper;
    }
}