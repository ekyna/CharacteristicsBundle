<?php

namespace Ekyna\Bundle\CharacteristicsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Ekyna\Bundle\CharacteristicsBundle\DependencyInjection
 * @author Étienne Dauvergne <contact@ekyna.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param boolean $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder
            ->root('ekyna_characteristics')
                ->children();

        $this->addClassMapSection($rootNode);
        $this->addSchemaSection($rootNode);
        $this->addMetadataSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param NodeBuilder $builder
     */
    private function addClassMapSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('classes')
                ->isRequired()
                ->requiresAtLeastOneElement()
                ->useAttributeAsKey('name')
                ->prototype('scalar')->end()
            ->end()
        ;
    }

    /**
     * @param NodeBuilder $builder
     */
    private function addSchemaSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('schema')
                ->addDefaultsIfNotSet()
                ->children()
                    /*->scalarNode('cache')->defaultValue('file')->end()
                    ->booleanNode('debug')->defaultValue($this->debug)->end()
                    ->arrayNode('file_cache')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('dir')->defaultValue('%kernel.cache_dir%/ekyna_characteristics/schema')->end()
                        ->end()
                    ->end()*/
                    ->booleanNode('auto_detection')->defaultTrue()->end()
                    ->arrayNode('directories')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param NodeBuilder $builder
     */
    private function addMetadataSection(NodeBuilder $builder)
    {
        $builder
            ->arrayNode('metadata')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('cache')->defaultValue('file')->end()
                    ->booleanNode('debug')->defaultValue($this->debug)->end()
                    ->arrayNode('file_cache')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('dir')->defaultValue('%kernel.cache_dir%/ekyna_characteristics/metadata')->end()
                        ->end()
                    ->end()
                    ->booleanNode('auto_detection')->defaultTrue()->end()
                    ->arrayNode('directories')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('path')->isRequired()->end()
                                ->scalarNode('namespace_prefix')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
