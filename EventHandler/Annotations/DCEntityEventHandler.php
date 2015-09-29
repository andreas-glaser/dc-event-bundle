<?php
namespace AndreasGlaser\DCEventBundle\EventHandler\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class DCEntityEventHandler
 *
 * @package AndreasGlaser\DCEventBundle\EventHandler\Annotations
 * @author  Andreas Glaser
 *
 * @Annotation
 * @Target("CLASS")
 */
final class DCEntityEventHandler extends Annotation
{
    public $class;
}