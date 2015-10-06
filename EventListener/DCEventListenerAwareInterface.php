<?php

namespace AndreasGlaser\DCEventBundle\EventListener;

/**
 * Interface DCEventListenerAwareInterface
 *
 * @package AndreasGlaser\DCEventBundle\EventListener
 * @author  Andreas Glaser
 */
interface DCEventListenerAwareInterface
{
    /**
     * @param \AndreasGlaser\DCEventBundle\EventListener\DCEventListener $dcEventListener
     *
     * @return mixed
     * @author Andreas Glaser
     */
    public function setDCEventListener(DCEventListener $dcEventListener = null);

    /**
     * @return bool
     * @author Andreas Glaser
     */
    public function hasDCEventListener();

    /**
     * @param bool $throwException
     *
     * @return DCEventListener|bool
     * @author Andreas Glaser
     */
    public function getDCEventListener($throwException = true);

    /**
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @throws \Exception
     * @author Andreas Glaser
     */
    public function flagDCEventListenerSet($flagName, $entity);

    /**
     * Checks if an entity flag has been set
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagDCEventListenerExists($flagName, $entity);

    /**
     * Removes an entity flag
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagDCEventListenerRemove($flagName, $entity);

    /**
     * Checks if an entity flag exists and removes it.
     *
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @author Andreas Glaser
     */
    public function flagDCEventListenerExistsAndRemove($flagName, $entity);
}