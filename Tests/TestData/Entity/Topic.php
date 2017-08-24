<?php

namespace AndreasGlaser\DCEventBundle\Tests\TestData\Entity;

use AndreasGlaser\DCEventBundle\EventHandler\Annotations\DCEntityEventHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Topic
 *
 * @package AndreasGlaser\DCEventBundle\Tests\TestData\Entity
 *
 * @ORM\Entity()
 * @ORM\Table()
 * @DCEntityEventHandler(class="AndreasGlaser\DCEventBundle\Tests\TestData\EEH\TopicEEH")
 */
class Topic
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
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    public $title;

    /**
     * @ORM\OneToOne(targetEntity="AndreasGlaser\DCEventBundle\Tests\TestData\Entity\Post")
     * @ORM\JoinColumn(nullable=false, unique=true)
     */
    public $originalPost;

    /**
     * @ORM\OneToOne(targetEntity="AndreasGlaser\DCEventBundle\Tests\TestData\Entity\Post")
     * @ORM\JoinColumn(nullable=false, unique=true)
     */
    public $latestPost;

    /**
     * @ORM\Column(type="datetimetz", nullable=false)
     */
    public $updatedAt;
}
