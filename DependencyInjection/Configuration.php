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
                //->booleanNode('toolbar')->defaultFalse()->end()
                ->booleanNode('toolbar')->defaultTrue()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}