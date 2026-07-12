<?php

namespace Kikwik\UserBundle\Tests\Integration\Command;

use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Kikwik\UserBundle\Tests\Factory\UserFactory;
use Symfony\Component\Console\Command\Command;

class UserCreateCommandTest extends BaseWebTestCase
{
    public function testCreateUser()
    {
        // check database is empty
        UserFactory::repository()->assert()->count(0);

        // execute kikwik:user:create command
        $commandTester = $this->getCommandTester('kikwik:user:create');
        $exitCode = $commandTester->execute([
            'username' => 'user@example.com',
            'password' => 'password123',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User user@example.com successfully created', $output);

        // check user was created in database
        UserFactory::repository()->assert()->count(1);
        $user = UserFactory::repository()->findOneBy(['username' => 'user@example.com']);
        
        self::assertSame('user@example.com', $user->getUsername());
        self::assertNotNull($user->getPassword());
        self::assertNotNull($user->getCreatedAt());
        self::assertSame('kikwik:user:create', $user->getCreatedBy());
        self::assertNotNull($user->getUpdatedAt());
        self::assertSame('kikwik:user:create', $user->getUpdatedBy());
        self::assertTrue($user->isEnabled());
        self::assertCount(1, $user->getRoles());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertNotContains('ROLE_SUPER_ADMIN', $user->getRoles());
        self::assertEquals(0, $user->getLoginCount());
    }

    public function testCreateUserAdmin()
    {
        // check database is empty
        UserFactory::repository()->assert()->count(0);

        // execute kikwik:user:create command
        $commandTester = $this->getCommandTester('kikwik:user:create');
        $exitCode = $commandTester->execute([
            'username' => 'admin@example.com',
            'password' => 'password123',
            '--super-admin' => true,
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User admin@example.com successfully created', $output);

        // check user was created in database
        UserFactory::repository()->assert()->count(1);
        $user = UserFactory::repository()->findOneBy(['username' => 'admin@example.com']);
        
        self::assertSame('admin@example.com', $user->getUsername());
        self::assertNotNull($user->getPassword());
        self::assertNotNull($user->getCreatedAt());
        self::assertSame('kikwik:user:create', $user->getCreatedBy());
        self::assertNotNull($user->getUpdatedAt());
        self::assertSame('kikwik:user:create', $user->getUpdatedBy());
        self::assertTrue($user->isEnabled());
        self::assertCount(2, $user->getRoles());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertContains('ROLE_SUPER_ADMIN', $user->getRoles());
        self::assertEquals(0, $user->getLoginCount());
    }
}