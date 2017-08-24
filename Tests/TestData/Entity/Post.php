<?php

namespace AndreasGlaser\DCEventBundle\Tests\TestData\Entity;

use AndreasGlaser\DCEventBundle\EventHandler\Annotations\DCEntityEventHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Post
 *
 * @package AndreasGlaser\DCEventBundle\Tests\TestData\Entity
 *
 * @ORM\Entity()
 * @ORM\Table()
 * @DCEntityEventHandler(class="AndreasGlaser\DCEventBundle\Tests\TestData\EEH\PostEEH")
 */
class Post
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="AndreasGlaser\DCEventBundle\Tests\TestData\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    public $user;

    /**
     * @ORM\Column(type="text", nullable=false)
     */
    public $content;

    /**
     * @ORM\Column(type="datetimetz", nullable=false)
     */
    public $createdAt;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    public $modifiedAt;
}
