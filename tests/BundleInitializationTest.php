<?php


namespace Kikwik\UserBundle\Tests;


use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Kikwik\UserBundle\Command\UserCreateCommand;
use Kikwik\UserBundle\Command\UserDeleteCommand;
use Kikwik\UserBundle\Command\UserEditCommand;
use Kikwik\UserBundle\Controller\PasswordController;
use Kikwik\UserBundle\EventSubscriber\LoginSubscriber;
use Kikwik\UserBundle\KikwikUserBundle;
use Kikwik\UserBundle\Security\UserChecker;
use Nyholm\BundleTest\BaseBundleTestCase;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;

class BundleInitializationTest extends BaseBundleTestCase
{
    protected function getBundleClass()
    {
        return KikwikUserBundle::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->addCompilerPass(new PublicServicePass('|kikwik_user.*|'));
    }

    public function testInitBundle()
    {
        // Create a new Kernel
        $kernel = $this->createKernel();

        // Add some configuration
        $kernel->addConfigFile(__DIR__.'/config.yml');

        // Add some other bundles we depend on
        $kernel->addBundle(FrameworkBundle::class);
        $kernel->addBundle(DoctrineBundle::class);
        $kernel->addBundle(SecurityBundle::class);

        // Boot the kernel.
        $this->bootKernel();

        // Get the container
        $container = $this->getContainer();

        // Test if you services exists
        $services = [
            'kikwik_user.event_subscriber.login_subscriber' => LoginSubscriber::class,
            'kikwik_user.security.user_checker' => UserChecker::class,
            'kikwik_user.controller.password_controller' => PasswordController::class,
            'kikwik_user.command.user_create_command' => UserCreateCommand::class,
            'kikwik_user.command.user_delete_command' => UserDeleteCommand::class,
            'kikwik_user.command.user_edit_command' => UserEditCommand::class
        ];
        foreach($services as $serviceId => $serviceClass)
        {
            $this->assertTrue($container->has($serviceId),'Container has '.$serviceId);
            $service = $container->get($serviceId);
            $this->assertInstanceOf($serviceClass, $service, 'Service '.$serviceId.' is instance of '.$serviceClass);
        }
    }

}