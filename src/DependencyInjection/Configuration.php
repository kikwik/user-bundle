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
                ->scalarNode('user_class')->defaultValue('App\Entity\User')->cannotBeEmpty()->end()
                ->scalarNode('user_identifier_field')->defaultValue('username')->cannotBeEmpty()->end()
                ->scalarNode('user_email_field')->defaultNull()->end()
                ->integerNode('password_min_length')->defaultValue(8)->end()
                ->scalarNode('sender_email')->defaultNull()->end()
                ->scalarNode('sender_name')->defaultValue('')->end()
                ->booleanNode('enable_admin')->defaultTrue()->end()
            ->end()
        ;

        return $treeBuilder;
    }

}