<?php

namespace AndreasGlaser\DCEventBundle\EventHandler\Annotations;

use AndreasGlaser\Helpers\StringHelper;
use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Class DCEntityEventHandlerReader
 *
 * @package AndreasGlaser\DCEventBundle\EventHandler\Annotations
 * @author  Andreas Glaser
 */
class DCEntityEventHandlerReader
{
    /**
     * @param $entityClass
     *
     * @return null|string
     * @throws \Exception
     * @author Andreas Glaser
     */
    public static function get($entityClass)
    {
        $className = get_class($entityClass);

        // translate proxy classes
        if (StringHelper::startsWith($className, 'Proxies\\__CG__\\')) {
            $entityClass = substr($className, strlen('Proxies\\__CG__\\'));
        }

        $reader = new AnnotationReader();
        $apiMetaAnnotation = $reader
            ->getClassAnnotation(
                new \ReflectionClass(new $entityClass),
                'AndreasGlaser\\DCEventBundle\\EventHandler\\Annotations\\DCEntityEventHandler'
            );

        if (!$apiMetaAnnotation || !$apiMetaAnnotation->class) {
            return null;
        }

        if (!class_exists($apiMetaAnnotation->class)) {
            throw new \Exception(sprintf('DCEntityEventHandler class %s does not exist', $apiMetaAnnotation->class));
        }

        return $apiMetaAnnotation->class;
    }
}