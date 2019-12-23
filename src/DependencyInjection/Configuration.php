<?php

namespace Kikwik\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('kikwik_user');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                // todo: add configurations
            ->end()
        ;

        return $treeBuilder;
    }

}