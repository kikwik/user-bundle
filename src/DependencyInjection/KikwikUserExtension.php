<?php


namespace Kikwik\UserBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class KikwikUserExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $passwordController = $container->getDefinition('kikwik_user.controller.password_controller');
        $passwordController->setArgument(0, $config['user_class']);
        $passwordController->setArgument(1, $config['user_identifier_field']);
        $passwordController->setArgument(2, $config['user_email_field']);
        $passwordController->setArgument(3, $config['password_min_length']);

        $cerateUserCommand = $container->getDefinition('kikwik_user.command.user_create_command');
        $cerateUserCommand->setArgument(0, $config['user_class']);
        $cerateUserCommand->setArgument(1, $config['user_identifier_field']);

        $cerateUserCommand = $container->getDefinition('kikwik_user.command.user_delete_command');
        $cerateUserCommand->setArgument(0, $config['user_class']);
        $cerateUserCommand->setArgument(1, $config['user_identifier_field']);

        $cerateUserCommand = $container->getDefinition('kikwik_user.command.user_edit_command');
        $cerateUserCommand->setArgument(0, $config['user_class']);
        $cerateUserCommand->setArgument(1, $config['user_identifier_field']);
    }

}