<?php

namespace AndreasGlaser\DCEventBundle\Tests\TestData\Fixtures;

use AndreasGlaser\DCEventBundle\Tests\TestData\Entity;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ORM
 *
 * @package AndreasGlaser\DCEventBundle\Tests\TestData\Fixtures
 * @author  Andreas Glaser
 */
class ORM implements FixtureInterface
{
    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     *
     * @author Andreas Glaser
     */
    public function load(ObjectManager $manager)
    {
        // not in use yet
    }
}

