<?php

declare(strict_types=1);

namespace Lemonmind\ObjectUpdateLoggerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('lemonmind_object_update_logger');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('classes_to_log')->end()
                ->scalarNode('objects_to_log')->end()
                ->scalarNode('disable_class_log')->end()
                ->booleanNode('disable_class_log')->end()
                ->booleanNode('disable_object_log')->end()
            ->end();

        return $treeBuilder;
    }
}
