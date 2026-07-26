<?php

namespace Kikwik\UserBundle\Tests\Integration\EventSubscriber;

use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Kikwik\UserBundle\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class LoginSubscriberTest extends BaseWebTestCase
{
    public function testLoginCountLastAndPreviousTimestampAndIp(): void
    {
        // user with no login history
        $user = UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
        ]);
        self::assertEquals(0, $user->getLoginCount());
        self::assertNull($user->getLastLoginAt());
        self::assertNull($user->getLastLoginFromIp());
        self::assertNull($user->getPreviousLoginAt());
        self::assertNull($user->getPreviousLoginFromIp());

        // first login
        $client = $this->getTestClient();
        $beforeLoginTimestamp = time();
        $this->doLogin($client, 'mario', 'password');
        $afterLoginTimestamp = time();


        // refresh user
        $user = UserFactory::repository()->first();
        // check loginCount
        self::assertEquals(1, $user->getLoginCount());
        // check lastLoginAt
        $firstLoginAt = $user->getLastLoginAt();
        self::assertNotNull($firstLoginAt);
        self::assertGreaterThanOrEqual($beforeLoginTimestamp, $firstLoginAt->getTimestamp());
        self::assertLessThanOrEqual($afterLoginTimestamp, $firstLoginAt->getTimestamp());
        // check lastLoginFromIp
        $firstLoginFromIp = $user->getLastLoginFromIp();
        self::assertEquals('127.0.0.1', $firstLoginFromIp);
        // check previousLoginAt
        self::assertNull($user->getPreviousLoginAt());
        // check previousLoginFromIp
        self::assertNull($user->getPreviousLoginFromIp());



        // logout and second login
        $this->doLogout($client);
        sleep(1);
        $beforeLoginTimestamp = time();
        $this->doLogin($client, 'mario', 'password');
        $afterLoginTimestamp = time();

        // refresh user
        $user = UserFactory::repository()->first();
        // check loginCount
        self::assertEquals(2, $user->getLoginCount());
        // check lastLoginAt
        $secondLoginAt = $user->getLastLoginAt();
        self::assertNotNull($secondLoginAt);
        self::assertGreaterThanOrEqual($beforeLoginTimestamp, $secondLoginAt->getTimestamp());
        self::assertLessThanOrEqual($afterLoginTimestamp, $secondLoginAt->getTimestamp());
        // check lastLoginFromIp
        self::assertEquals('127.0.0.1', $user->getLastLoginFromIp());
        // check previousLoginAt
        self::assertEquals($firstLoginAt, $user->getPreviousLoginAt());
        // check previousLoginFromIp
        self::assertEquals($firstLoginFromIp, $user->getPreviousLoginFromIp());

    }



}