<?php


namespace Kikwik\UserBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Yaml;

class KikwikUserExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        // stof_doctrine_extensions configuration
        $stofDoctrineExtensionConfig = Yaml::parseFile(__DIR__.'/../Resources/config/bundles/stof_doctrine_extensions.yaml');
        $container->prependExtensionConfig('stof_doctrine_extensions', $stofDoctrineExtensionConfig);

        // kikwik_admin configuration
        $configs = $container->getExtensionConfig($this->getAlias());
        $enableAdmin = !isset($configs[0]['enable_admin']) || $configs[0]['enable_admin'];

        if($enableAdmin)
        {
            $bundles = $container->getParameter('kernel.bundles');
            if (isset($bundles['KikwikAdminBundle']))
            {
                $configForAdmin = Yaml::parseFile(__DIR__.'/../Resources/config/bundles/kikwik_admin.yaml');

                if(isset($bundles['KikwikUserLogBundle']))
                {
                    $configForAdmin['admins']['user']['object']['actions']['logs'] = [
                        'label' => 'Logs',
                        'icon' => 'fas fa-history',
                        'condition' => [
                            'roles' => [ 'ROLE_ADMIN_USER_LOG_SESSION_LIST', 'ROLE_SUPER_ADMIN' ]
                        ]
                    ];
                }
                $container->prependExtensionConfig('kikwik_admin', $configForAdmin);
            }
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $passwordController = $container->getDefinition('kikwik_user.controller.password_controller');
        $passwordController->setArgument('$userClass', $config['user_class']);
        $passwordController->setArgument('$userIdentifierField', $config['user_identifier_field']);
        $passwordController->setArgument('$userEmailField', $config['user_email_field']);
        $passwordController->setArgument('$passwordMinLength', $config['password_min_length']);
        $passwordController->setArgument('$senderEmail', $config['sender_email']);
        $passwordController->setArgument('$senderName', $config['sender_name']);

        $cerateUserCommand = $container->getDefinition('kikwik_user.command.user_create_command');
        $cerateUserCommand->setArgument('$userClass', $config['user_class']);
        $cerateUserCommand->setArgument('$userIdentifierField', $config['user_identifier_field']);

        $cerateUserCommand = $container->getDefinition('kikwik_user.command.user_delete_command');
        $cerateUserCommand->setArgument('$userClass', $config['user_class']);
        $cerateUserCommand->setArgument('$userIdentifierField', $config['user_identifier_field']);

        $cerateUserCommand = $container->getDefinition('kikwik_user.command.user_edit_command');
        $cerateUserCommand->setArgument('$userClass', $config['user_class']);
        $cerateUserCommand->setArgument('$userIdentifierField', $config['user_identifier_field']);
    }

}