<?php

namespace AndreasGlaser\DCEventBundle\Helper;

/**
 * Class FlagHelper
 *
 * @package AndreasGlaser\DCEventBundle\Helper
 * @author  Andreas Glaser
 */
class FlagHelper
{
    /**
     * @var array
     */
    protected $flags = [];

    /**
     * @return \AndreasGlaser\DCEventBundle\Helper\FlagHelper
     * @author Andreas Glaser
     */
    public static function factory()
    {
        return new FlagHelper();
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
}