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
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /**
         * @var TestKernel $kernel
         */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(KikwikUserBundle::class);
        $kernel->handleOptions($options);



        return $kernel;
    }

    public function testInitBundle(): void
    {

        // Boot the kernel with a config closure, the handleOptions call in createKernel is important for that to work
        $kernel = self::bootKernel(['config' => static function(TestKernel $kernel){
            // Add some other bundles we depend on
            $kernel->addTestBundle(FrameworkBundle::class);
            $kernel->addTestBundle(DoctrineBundle::class);
            $kernel->addTestBundle(SecurityBundle::class);


            // Add some configuration
            $kernel->addTestConfig(__DIR__.'/config.yml');
        }]);

        // Get the container
        // $container = $kernel->getContainer();

        // Or for FrameworkBundle@^5.3.6 to access private services without the PublicCompilerPass
        $container = self::getContainer();


        // Test if your services exists
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
            $this->assertTrue($container->has($serviceId),'Container must have '.$serviceId);
            $service = $container->get($serviceId);
            $this->assertInstanceOf($serviceClass, $service, 'Service '.$serviceId.' must be an instance of '.$serviceClass);
        }
    }

}