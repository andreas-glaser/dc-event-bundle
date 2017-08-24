<?php

namespace AndreasGlaser\DCEventBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package AndreasGlaser\DCEventBundle\DependencyInjection
 * @author  Andreas Glaser
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('andreas_glaser_dc_event', 'array');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultValue(true)->end()
            ->scalarNode('common_entity_event_handler')->defaultValue(null)->end()
            ->end();

        return $treeBuilder;
    }
}
