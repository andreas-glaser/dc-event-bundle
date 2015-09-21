<?php
namespace AndreasGlaser\DCEventBundle\EntityEventHandler\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class DCEntityEventHandler
 *
 * @package AndreasGlaser\DCEventBundle\EntityEventHandler\Annotations
 * @author  Andreas Glaser
 *
 * @Annotation
 * @Target("CLASS")
 */
final class DCEntityEventHandler extends Annotation
{
    public $class;
}