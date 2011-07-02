<?php

namespace Sf2gen\Bundle\ConsoleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sf2gen_console');

        $rootNode
            ->children()
                ->booleanNode('new_process')->defaultFalse()->end()
                ->booleanNode('toolbar')->defaultTrue()->end()
                ->booleanNode('all')->defaultFalse()->end()
                ->booleanNode('local')->defaultTrue()->end()
                ->arrayNode('apps')
                    ->treatFalseLike(array())
                    ->prototype('scalar')
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}