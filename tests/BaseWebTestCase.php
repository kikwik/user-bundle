<?php

namespace Kikwik\UserBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
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

    private ?KernelBrowser $client = null;

    protected function tearDown(): void
    {
        $this->application = null;
        $this->client = null;

        parent::tearDown();
    }

    protected function bootTestKernel(): void
    {
        if (self::$kernel === null) {
            self::bootKernel();
        }
    }

    protected function getTestContainer(): ContainerInterface
    {
        if (self::$kernel === null) {
            $this->bootTestKernel();
        }

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
        if (self::$kernel !== null) {
            self::ensureKernelShutdown();
        }

        $this->application = null;
        $this->client = static::createClient($options, $server);

        return $this->client;
    }

    protected function getTestClient(): KernelBrowser
    {
        if ($this->client === null) {
            $this->client = $this->createTestClient();
        }

        return $this->client;
    }

    protected function doLogin(KernelBrowser $client, string $username, string $password)
    {
        $crawler = $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Login')->form([
            '_username' => $username,
            '_password' => $password,
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/profile');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Profile');
    }

    protected function doLogout(KernelBrowser $client)
    {
        $client->request('GET', '/logout');
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    protected function getPropertyValue(mixed $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        return $reflection->getValue($object);
    }
}