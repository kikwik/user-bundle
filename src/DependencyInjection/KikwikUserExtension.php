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

        $cerateUserCommand = $container->getDefinition('kikwik_user.command.create_user_command');
        $cerateUserCommand->setArgument(0, $config['user_class']);
        $cerateUserCommand->setArgument(1, $config['user_identifier_field']);

        $changeUserPasswordCommand = $container->getDefinition('kikwik_user.command.change_user_password_command');
        $changeUserPasswordCommand->setArgument(0, $config['user_class']);
        $changeUserPasswordCommand->setArgument(1, $config['user_identifier_field']);

        $changeUserRolesCommand = $container->getDefinition('kikwik_user.command.change_user_roles_command');
        $changeUserRolesCommand->setArgument(0, $config['user_class']);
        $changeUserRolesCommand->setArgument(1, $config['user_identifier_field']);
    }

}