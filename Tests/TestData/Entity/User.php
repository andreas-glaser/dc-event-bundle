<?php

namespace AndreasGlaser\DCEventBundle\Tests\TestData\Entity;

use AndreasGlaser\DCEventBundle\EventHandler\Annotations\DCEntityEventHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class User
 *
 * @package AndreasGlaser\DCEventBundle\Tests\TestData\Entity
 *
 * @ORM\Entity()
 * @ORM\Table()
 * @DCEntityEventHandler(class="AndreasGlaser\DCEventBundle\Tests\TestData\EEH\UserEEH")
 */
class User
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=320, nullable=false, unique=true)
     */
    public $email;

    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    public $name;

    /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    public $password;

    /**
     * @var string
     */
    public $passwordPlain;

}
