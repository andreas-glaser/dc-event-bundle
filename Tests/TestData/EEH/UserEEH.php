<?php

namespace AndreasGlaser\DCEventBundle\Tests\TestData\EEH;

use AndreasGlaser\DCEventBundle\EventHandler\DCEntityEventHandlerBase;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;

/**
 * Class UserEEH
 *
 * @package AndreasGlaser\DCEventBundle\Tests\TestData\EEH
 */
class UserEEH extends DCEntityEventHandlerBase
{
    /**
     * @var \AndreasGlaser\DCEventBundle\Tests\TestData\Entity\User
     */
    protected $entity;

    /**
     * @inheritdoc
     */
    public function prePersist()
    {
        if ($this->entity->passwordPlain) {
            $this->entity->password = md5($this->entity->passwordPlain);
        }
    }

    /**
     * @inheritdoc
     */
    public function postPersist()
    {
        // TODO: Implement postPersist() method.
    }

    /**
     * @inheritdoc
     */
    public function preUpdate(ChangeSetHelper $changeSet)
    {
        // TODO: Implement preUpdate() method.
    }

    /**
     * @inheritdoc
     */
    public function postUpdate()
    {
        // TODO: Implement postUpdate() method.
    }

    /**
     * @inheritdoc
     */
    public function preRemove()
    {
        // TODO: Implement preRemove() method.
    }

    /**
     * @inheritdoc
     */
    public function postRemove()
    {
        // TODO: Implement postRemove() method.
    }
}