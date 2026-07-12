<?php

namespace Kikwik\UserBundle\Tests\Integration;

use Kikwik\UserBundle\Command\UserCreateCommand;
use Kikwik\UserBundle\Command\UserDeleteCommand;
use Kikwik\UserBundle\Command\UserEditCommand;
use Kikwik\UserBundle\Controller\PasswordController;
use Kikwik\UserBundle\EventSubscriber\LoginSubscriber;
use Kikwik\UserBundle\Security\UserChecker;
use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


class KikwikUserBundleTest extends BaseWebTestCase
{

    public function testCanAccessService()
    {
        $container = self::getContainer();

        $services = [
            'kikwik_user.event_subscriber.login_subscriber' => LoginSubscriber::class,
//            'kikwik_user.security.user_checker' => UserChecker::class,  // TODO: il servizio non è public
//            'kikwik_user.controller.password_controller' => PasswordController::class, // TODO: nei test non c'è la session (per ora)
            'kikwik_user.command.user_create_command' => UserCreateCommand::class,
            'kikwik_user.command.user_delete_command' => UserDeleteCommand::class,
            'kikwik_user.command.user_edit_command' => UserEditCommand::class
        ];
        foreach($services as $serviceId => $serviceClass)
        {
            $this->assertTrue($container->has($serviceId),'Container must have '.$serviceId);
            $service = $container->get($serviceId);
            $this->assertInstanceOf($serviceClass, $service, 'Service '.$serviceId.' must be an instance of '.$serviceClass);
        }
    }
}