<?php

namespace AndreasGlaser\DCEventBundle\Tests;

use AndreasGlaser\DCEventBundle\DependencyInjection\AndreasGlaserDCEventExtension;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Class TestCase
 *
 * @package AndreasGlaser\DCEventBundle\Tests
 * @author  Andreas Glaser
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    public function createYamlBundleTestContainer()
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug'       => false,
            'kernel.bundles'     => ['YamlBundle' => 'Fixtures\Bundles\YamlBundle\YamlBundle'],
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__ . '/../../../../', // src dir
        ]));

        $container->set('annotation_reader', new AnnotationReader());
        $extension = new AndreasGlaserDCEventExtension();
        $container->registerExtension($extension);
        $extension->load([
            [
                'enabled' => true,
            ],
        ], $container);

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveDefinitionTemplatesPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}