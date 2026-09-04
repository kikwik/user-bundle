<?php

declare(strict_types=1);

use Kikwik\UserBundle\Command\UserCreateCommand;
use Kikwik\UserBundle\Command\UserDeleteCommand;
use Kikwik\UserBundle\Command\UserEditCommand;
use Kikwik\UserBundle\Controller\PasswordController;
use Kikwik\UserBundle\EventSubscriber\LoginSubscriber;
use Kikwik\UserBundle\Security\UserChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services // LoginSubscriber
        ->set('kikwik_user.event_subscriber.login_subscriber', LoginSubscriber::class)
        ->args([
            service('doctrine.orm.entity_manager'),
        ])
        ->tag('kernel.event_subscriber', [
            'event' => 'security.interactive_login',
        ]);

    $services // UserChecker
        ->set('kikwik_user.security.user_checker', UserChecker::class)
        ->alias(UserChecker::class, 'kikwik_user.security.user_checker');

    $services // PasswordController
        ->set('kikwik_user.controller.password_controller', PasswordController::class)
        ->public()
        ->args([
            service('doctrine.orm.entity_manager'),
            service('security.authorization_checker'),
            service('form.factory'),
            service('twig'),
            service('security.token_storage'),
            service('router'),
            service('security.user_password_hasher'),
            service('translator'),
            service('mailer.mailer'),
        ]);

    $services // UserCreateCommand
        ->set('kikwik_user.command.user_create_command', UserCreateCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('security.user_password_hasher'),
        ])
        ->tag('console.command', [
            'command' => 'kikwik:user:create',
        ]);

    $services // UserDeleteCommand
        ->set('kikwik_user.command.user_delete_command', UserDeleteCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
        ])
        ->tag('console.command', [
            'command' => 'kikwik:user:delete',
        ]);

    $services // UserEditCommand
        ->set('kikwik_user.command.user_edit_command', UserEditCommand::class)
        ->args([
            service('doctrine.orm.entity_manager'),
            service('security.user_password_hasher'),
        ])
        ->tag('console.command', [
            'command' => 'kikwik:user:edit',
        ]);
};