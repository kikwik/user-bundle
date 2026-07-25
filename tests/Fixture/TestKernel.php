<?php

namespace Kikwik\UserBundle\Tests\Fixture;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Kikwik\UserBundle\KikwikUserBundle;
use Kikwik\UserBundle\Tests\Fixture\Entity\User;
use Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new SecurityBundle();
        yield new StofDoctrineExtensionsBundle();
        yield new TwigBundle();
        yield new ZenstruckFoundryBundle();
        yield new KikwikUserBundle();
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getProjectDir().'/src/Resources/config/routes.php');
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure();

        $services
            ->load('Kikwik\\UserBundle\\Tests\\Factory\\', '../Factory/')
            ->public();

        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
            'router' => [
                'utf8' => true,
            ],
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
            'csrf_protection' => true,
            'mailer' => [
                'dsn' => 'null://null',
            ],
        ]);

        $container->extension('kikwik_user', [
            'user_class' => User::class,
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'url' => 'sqlite:///%kernel.project_dir%/var/data.db',
            ],
            'orm' => [
                'mappings' => [
                    'Test' => [
                        'dir' => '%kernel.project_dir%/tests/Fixture/Entity',
                        'prefix' => 'Kikwik\UserBundle\Tests\Fixture\Entity',
                    ]
                ]
            ],
        ]);

        $container->extension('security', [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => 'auto',
            ],
            'providers' => [
                'bundle_user_provider' => [
                    'entity' => [
                        'class' => User::class,
                        'property' => 'username',
                    ],
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'main' => [
                    'lazy' => true,
                    'provider' => 'bundle_user_provider',
                ],
            ],
        ]);
    }

}