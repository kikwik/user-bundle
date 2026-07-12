<?php

namespace Kikwik\UserBundle\Tests\Integration\Command;

use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Kikwik\UserBundle\Tests\Factory\UserFactory;
use Kikwik\UserBundle\Tests\Fixture\Entity\User;
use Symfony\Component\Console\Command\Command;

class UserEditCommandTest extends BaseWebTestCase
{
    public function testChangeNothing()
    {
        // create a user
        $originalUser = UserFactory::createOne([
            'username' => 'change-me@example.com',
            'password' => 'password',
            'roles' => ['ROLE_EDITOR'],
            'isEnabled' => true,
        ]);
        $oldPasswordHash = $originalUser->getPassword();
        UserFactory::repository()->assert()->count(1);

        // execute kikwik:user:edit command
        $commandTester = $this->getCommandTester('kikwik:user:edit');
        $commandTester->setInputs([
            '', // password
            '', // roles
            '', // isEnabled
        ]);
        $exitCode = $commandTester->execute([
            'username' => 'change-me@example.com',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User change-me@example.com successfully edited', $output);

        // check user was created in database
        UserFactory::repository()->assert()->count(1);
        $user = UserFactory::repository()->findOneBy(['username' => 'change-me@example.com']);

        self::assertEquals('change-me@example.com', $user->getUsername());
        self::assertEquals($oldPasswordHash, $user->getPassword());
        self::assertEquals(['ROLE_EDITOR', 'ROLE_USER'], array_values($user->getRoles()));
        self::assertEquals(true, $user->isEnabled());
        self::assertEquals('kikwik:user:edit', $user->getUpdatedBy());
    }


    public function testChangePassword()
    {
        // create a user
        $originalUser = UserFactory::createOne([
            'username' => 'change-me@example.com',
            'password' => 'password',
            'roles' => ['ROLE_EDITOR'],
            'isEnabled' => true,
        ]);
        $oldPasswordHash = $originalUser->getPassword();
        UserFactory::repository()->assert()->count(1);

        // execute kikwik:user:edit command
        $commandTester = $this->getCommandTester('kikwik:user:edit');
        $commandTester->setInputs([
            'new-password', // password
            '', // roles
            '', // isEnabled
        ]);
        $exitCode = $commandTester->execute([
            'username' => 'change-me@example.com',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User change-me@example.com successfully edited', $output);

        // check user was created in database
        UserFactory::repository()->assert()->count(1);
        $user = UserFactory::repository()->findOneBy(['username' => 'change-me@example.com']);

        self::assertEquals('change-me@example.com', $user->getUsername());
        self::assertNotEquals($oldPasswordHash, $user->getPassword());
        self::assertEquals(['ROLE_EDITOR', 'ROLE_USER'], array_values($user->getRoles()));
        self::assertEquals(true, $user->isEnabled());
        self::assertEquals('kikwik:user:edit', $user->getUpdatedBy());
    }

    public function testChangeRole()
    {
        // create a user
        $originalUser = UserFactory::createOne([
            'username' => 'change-me@example.com',
            'password' => 'password',
            'roles' => ['ROLE_EDITOR'],
            'isEnabled' => true,
        ]);
        $oldPasswordHash = $originalUser->getPassword();
        UserFactory::repository()->assert()->count(1);

        // execute kikwik:user:edit command
        $commandTester = $this->getCommandTester('kikwik:user:edit');
        $commandTester->setInputs([
            '', // password
            'ROLE_ADMIN, ROLE_SUPERMAN', // roles
            '', // isEnabled
        ]);
        $exitCode = $commandTester->execute([
            'username' => 'change-me@example.com',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User change-me@example.com successfully edited', $output);

        // check user was created in database
        UserFactory::repository()->assert()->count(1);
        $user = UserFactory::repository()->findOneBy(['username' => 'change-me@example.com']);

        self::assertEquals('change-me@example.com', $user->getUsername());
        self::assertEquals($oldPasswordHash, $user->getPassword());
        self::assertEquals(['ROLE_ADMIN', 'ROLE_SUPERMAN', 'ROLE_USER'], array_values($user->getRoles()));
        self::assertEquals(true, $user->isEnabled());
        self::assertEquals('kikwik:user:edit', $user->getUpdatedBy());
    }

    public function testChangeEnabled()
    {
        // create a user
        $originalUser = UserFactory::createOne([
            'username' => 'change-me@example.com',
            'password' => 'password',
            'roles' => ['ROLE_EDITOR'],
            'isEnabled' => true,
        ]);
        $oldPasswordHash = $originalUser->getPassword();
        UserFactory::repository()->assert()->count(1);

        // execute kikwik:user:edit command
        $commandTester = $this->getCommandTester('kikwik:user:edit');
        $commandTester->setInputs([
            '', // password
            '', // roles
            'no', // isEnabled
        ]);
        $exitCode = $commandTester->execute([
            'username' => 'change-me@example.com',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User change-me@example.com successfully edited', $output);

        // check user was created in database
        UserFactory::repository()->assert()->count(1);
        $user = UserFactory::repository()->findOneBy(['username' => 'change-me@example.com']);

        self::assertEquals('change-me@example.com', $user->getUsername());
        self::assertEquals($oldPasswordHash, $user->getPassword());
        self::assertEquals(['ROLE_EDITOR', 'ROLE_USER'], array_values($user->getRoles()));
        self::assertEquals(false, $user->isEnabled());
        self::assertEquals('kikwik:user:edit', $user->getUpdatedBy());
    }
}