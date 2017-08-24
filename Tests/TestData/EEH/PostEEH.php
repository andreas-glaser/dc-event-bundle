<?php

namespace AndreasGlaser\DCEventBundle\Tests\TestData\EEH;

use AndreasGlaser\DCEventBundle\EventHandler\Annotations\DCEntityEventHandler;
use AndreasGlaser\DCEventBundle\EventHandler\DCEntityEventHandlerBase;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PostEEH
 *
 * @package AndreasGlaser\DCEventBundle\Tests\TestData\EEH
 * @author  Andreas Glaser
 */
class PostEEH extends DCEntityEventHandlerBase
{
    /**
     * @return void
     * @author Andreas Glaser
     */
    public function prePersist()
    {
        // TODO: Implement prePersist() method.
    }

    /**
     * @return void
     * @author Andreas Glaser
     */
    public function postPersist()
    {
        // TODO: Implement postPersist() method.
    }

    /**
     * @param \AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper $changeSet
     *
     * @return void
     * @author Andreas Glaser
     */
    public function preUpdate(ChangeSetHelper $changeSet)
    {
        // TODO: Implement preUpdate() method.
    }

    /**
     * @return void
     * @author Andreas Glaser
     */
    public function postUpdate()
    {
        // TODO: Implement postUpdate() method.
    }

    /**
     * @return void
     * @author Andreas Glaser
     */
    public function preRemove()
    {
        // TODO: Implement preRemove() method.
    }

    /**
     * @return void
     * @author Andreas Glaser
     */
    public function postRemove()
    {
        // TODO: Implement postRemove() method.
    }
}