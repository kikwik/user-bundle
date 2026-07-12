<?php

namespace Kikwik\UserBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class BaseWebTestCase extends WebTestCase
{
    use Factories, ResetDatabase;

    private ?Application $application = null;

    protected function bootTestKernel(): void
    {
        if (self::$kernel === null) {
            self::bootKernel();
        }
    }

    protected function getTestContainer(): ContainerInterface
    {
        $this->bootTestKernel();

        return static::getContainer();
    }

    protected function getService(string $id): object
    {
        return $this->getTestContainer()->get($id);
    }

    protected function getConsoleApplication(): Application
    {
        if ($this->application === null) {
            $this->bootTestKernel();

            $this->application = new Application(self::$kernel);
            $this->application->setAutoExit(false);
        }

        return $this->application;
    }

    protected function getCommand(string $name): Command
    {
        return $this->getConsoleApplication()->find($name);
    }

    protected function getCommandTester(string $name): CommandTester
    {
        return new CommandTester($this->getCommand($name));
    }

    protected function createTestClient(array $options = [], array $server = []): KernelBrowser
    {
        return static::createClient($options, $server);
    }
}