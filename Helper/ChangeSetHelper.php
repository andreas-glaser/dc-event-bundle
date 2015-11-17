<?php

namespace AndreasGlaser\DCEventBundle\Helper;

/**
 * Class ChangeSetHelper
 *
 * @package AndreasGlaser\DCEventBundle\Helper
 * @author  Andreas Glaser
 */
class ChangeSetHelper
{
    /**
     * @var array
     */
    protected $entityChangeSet = [];

    /**
     * @param array $entityChangeSet
     *
     * @return \AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper
     * @author Andreas Glaser
     */
    public static function factory(array $entityChangeSet)
    {
        return new ChangeSetHelper($entityChangeSet);
    }

    /**
     * ChangeSetHelper constructor.
     *
     * @param array $entityChangeSet
     */
    public function __construct(array $entityChangeSet)
    {
        $this->entityChangeSet = $entityChangeSet;
    }

    /**
     * Retrieves entity changeset.
     *
     * @return array
     */
    public function getEntityChangeSet()
    {
        return $this->entityChangeSet;
    }

    /**
     * Checks if field has a changeset.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasChangedField($field)
    {
        return isset($this->entityChangeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getOldValue($field)
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return void
     */
    public function setNewValue($field, $value)
    {
        $this->assertValidField($field);

        $this->entityChangeSet[$field][1] = $value;
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @param string $field
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertValidField($field)
    {
        if (!isset($this->entityChangeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the entity',
                $field
            ));
        }
    }
}