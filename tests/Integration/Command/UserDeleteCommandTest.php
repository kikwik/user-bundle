<?php

namespace Kikwik\UserBundle\Tests\Integration\Command;

use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Kikwik\UserBundle\Tests\Factory\UserFactory;
use Symfony\Component\Console\Command\Command;

class UserDeleteCommandTest extends BaseWebTestCase
{
    public function testDeleteUserWithConfirmation()
    {
        // create a user
        UserFactory::createOne([
            'username' => 'delete-me@example.com',
        ]);
        UserFactory::repository()->assert()->count(1);

        // execute kikwik:user:delete command
        $commandTester = $this->getCommandTester('kikwik:user:delete');
        $commandTester->setInputs([
            'yes',
        ]);
        $exitCode = $commandTester->execute([
            'username' => 'delete-me@example.com',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User delete-me@example.com successfully deleted', $output);


        // check database is empty
        UserFactory::repository()->assert()->count(0);
    }

    public function testDeleteUserAbort()
    {
        // create a user
        UserFactory::createOne([
            'username' => 'dont-delete-me@example.com',
        ]);
        UserFactory::repository()->assert()->count(1);

        // execute kikwik:user:delete command
        $commandTester = $this->getCommandTester('kikwik:user:delete');
        $commandTester->setInputs([
            'no',
        ]);
        $exitCode = $commandTester->execute([
            'username' => 'dont-delete-me@example.com',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User dont-delete-me@example.com was NOT deleted', $output);


        // check database is not empty
        UserFactory::repository()->assert()->count(1);
    }

    public function testDeleteNotExistingUser()
    {
        // check database is empty
        UserFactory::repository()->assert()->count(0);

        // execute kikwik:user:delete command
        $commandTester = $this->getCommandTester('kikwik:user:delete');
        $exitCode = $commandTester->execute([
            'username' => 'ghost@example.com',
        ]);
        self::assertSame(Command::SUCCESS, $exitCode);
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('User ghost@example.com does not exists', $output);

        // check database is empty
        UserFactory::repository()->assert()->count(0);
    }
}