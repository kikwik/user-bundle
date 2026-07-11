<?php

namespace Kikwik\UserBundle\Tests\Fixture;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Kikwik\UserBundle\KikwikUserBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new SecurityBundle();
        yield new TwigBundle();
        yield new KikwikUserBundle();
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $container->extension('framework', [
            'test' => true,
            'secret' => 'test',
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
                UserPasswordHasherInterface::class => 'auto',
            ],
            'providers' => [
                'users_in_memory' => [
                    'memory' => null,
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'main' => [
                    'lazy' => true,
                    'provider' => 'users_in_memory',
                ],
            ],
        ]);
    }

}