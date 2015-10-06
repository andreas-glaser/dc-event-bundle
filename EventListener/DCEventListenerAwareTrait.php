<?php

namespace AndreasGlaser\DCEventBundle\EventListener;

/**
 * Class DCEventListenerAwareTrait
 *
 * @package AndreasGlaser\DCEventBundle\EventListener
 * @author  Andreas Glaser
 */
trait DCEventListenerAwareTrait
{
    /**
     * @var DCEventListener|null
     */
    protected $dcEventListener = null;

    /**
     * @param \AndreasGlaser\DCEventBundle\EventListener\DCEventListener $dcEventListener
     *
     * @return $this
     * @author Andreas Glaser
     */
    public function setDCEventListener(DCEventListener $dcEventListener = null)
    {
        $this->dcEventListener = $dcEventListener;

        return $this;
    }

    /**
     * @param bool $throwException
     *
     * @return \AndreasGlaser\DCEventBundle\EventListener\DCEventListener|bool
     * @throws \Exception
     * @author Andreas Glaser
     */
    public function getDCEventListener($throwException = true)
    {
        if ($this->hasDCEventListener()) {
            return $this->dcEventListener;
        } else {

            if ($throwException) {
                throw new \Exception('DCEventListener has not been bound');
            }
        }

        return false;
    }

    /**
     * @return bool
     * @author Andreas Glaser
     */
    public function hasDCEventListener()
    {
        return $this->dcEventListener instanceof DCEventListener;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function dcEventListenerPersist($entity)
    {
        $this->getDCEventListener(true)->persist($entity);

        return $this;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function dcEventListenerRecalculate($entity)
    {
        $this->getDCEventListener(true)->recalculate($entity);

        return $this;
    }

    /**
     * @param $entity
     *
     * @return $this
     * @author Andreas Glaser
     */
    protected function dcEventListenerRemove($entity)
    {
        $this->getDCEventListener(true)->remove($entity);

        return $this;
    }

    /**
     * @param $flagName
     * @param $entity
     *
     * @return bool
     * @throws \Exception
     * @author Andreas Glaser
     */
    public function flagDCEventListenerSet($flagName, $entity)
    {
        return $this->getDCEventListener(true)->getFlagHelper()->flagSet($flagName, $entity);
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
    public function flagDCEventListenerExists($flagName, $entity)
    {
        return $this->getDCEventListener(true)->getFlagHelper()->flagExists($flagName, $entity);
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
    public function flagDCEventListenerRemove($flagName, $entity)
    {
        return $this->getDCEventListener(true)->getFlagHelper()->flagRemove($flagName, $entity);
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
    public function flagDCEventListenerExistsAndRemove($flagName, $entity)
    {
        return $this->getDCEventListener(true)->getFlagHelper()->flagExistsAndRemove($flagName, $entity);
    }
}