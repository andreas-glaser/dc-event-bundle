# DCEventBundle - Doctrine Custom Event Bundle

This bundle attaches an event handler during preFlush, allowing you to persist, update and remove entities while having access to change sets.

##Installation
```bash
composer require andreas-glaser/dc-event-bundle ^1
```

##Usage

###1.) Attach entity event listener
```php
<?php

namespace AppBundle\Entity;

use AndreasGlaser\DCEventBundle\EventHandler\Annotations\DCEntityEventHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * Article
 *
 * @ORM\Table(name="article")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ArticleRepository")
 * @DCEntityEventHandler(class="AppBundle\EEH\ArticleEEH")
 */
class Article
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    private $body;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return Article
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return Article
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}
```

###2.) Create entity event handler
```php
<?php

namespace AppBundle\EEH;

use AndreasGlaser\DCEventBundle\EventHandler\DCEntityEventHandlerBase;
use AndreasGlaser\DCEventBundle\Helper\ChangeSetHelper;
use AppBundle\Entity as AppBundleEntity;

/**
 * Class ArticleEEH
 *
 * @package AppBundle\EEH
 */
class ArticleEEH extends DCEntityEventHandlerBase
{
    /**
     * @return void
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
     */
    public function preUpdate(ChangeSetHelper $changeSet)
    {
        // TODO: Implement preUpdate() method.
    }

    /**
     * @return void
     */
    public function postUpdate()
    {
        // TODO: Implement postUpdate() method.
    }

    /**
     * @return void
     */
    public function preRemove()
    {
        // TODO: Implement preRemove() method.
    }

    /**
     * @return void
     */
    public function postRemove()
    {
        // TODO: Implement postRemove() method.
    }
}
```