<?php

namespace AndreasGlaser\DCEventBundle\Extras;

use AndreasGlaser\DCEventBundle\EventListener\DCEventListenerAwareInterface;
use AndreasGlaser\DCEventBundle\EventListener\DCEventListenerAwareTrait;

/**
 * Class EntityServiceBase
 *
 * @package AndreasGlaser\DCEventBundle\Extras
 * @author  Andreas Glaser
 */
class EntityServiceBase implements DCEventListenerAwareInterface
{
    use DCEventListenerAwareTrait;
}