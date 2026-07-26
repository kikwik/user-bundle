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
        $this->assertEquals(0, $user->getLoginCount());
        $this->assertNull($user->getLastLoginAt());
        $this->assertNull($user->getLastLoginFromIp());
        $this->assertNull($user->getPreviousLoginAt());
        $this->assertNull($user->getPreviousLoginFromIp());

        // first login
        $client = $this->createTestClient();
        $beforeLoginTimestamp = time();
        $this->doLogin($client);
        $afterLoginTimestamp = time();


        // refresh user
        $user = UserFactory::repository()->first();
        // check loginCount
        $this->assertEquals(1, $user->getLoginCount());
        // check lastLoginAt
        $firstLoginAt = $user->getLastLoginAt();
        $this->assertNotNull($firstLoginAt);
        $this->assertGreaterThanOrEqual($beforeLoginTimestamp, $firstLoginAt->getTimestamp());
        $this->assertLessThanOrEqual($afterLoginTimestamp, $firstLoginAt->getTimestamp());
        // check lastLoginFromIp
        $firstLoginFromIp = $user->getLastLoginFromIp();
        $this->assertEquals('127.0.0.1', $firstLoginFromIp);
        // check previousLoginAt
        $this->assertNull($user->getPreviousLoginAt());
        // check previousLoginFromIp
        $this->assertNull($user->getPreviousLoginFromIp());



        // logout and second login
        $this->doLogout($client);
        sleep(1);
        $beforeLoginTimestamp = time();
        $this->doLogin($client);
        $afterLoginTimestamp = time();

        // refresh user
        $user = UserFactory::repository()->first();
        // check loginCount
        $this->assertEquals(2, $user->getLoginCount());
        // check lastLoginAt
        $secondLoginAt = $user->getLastLoginAt();
        $this->assertNotNull($secondLoginAt);
        $this->assertGreaterThanOrEqual($beforeLoginTimestamp, $secondLoginAt->getTimestamp());
        $this->assertLessThanOrEqual($afterLoginTimestamp, $secondLoginAt->getTimestamp());
        // check lastLoginFromIp
        $this->assertEquals('127.0.0.1', $user->getLastLoginFromIp());
        // check previousLoginAt
        $this->assertEquals($firstLoginAt, $user->getPreviousLoginAt());
        // check previousLoginFromIp
        $this->assertEquals($firstLoginFromIp, $user->getPreviousLoginFromIp());

    }


    private function doLogin(KernelBrowser $client)
    {
        $crawler = $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Login')->form([
            '_username' => 'mario',
            '_password' => 'password',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/profile');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Profile');
    }

    private function doLogout(KernelBrowser $client)
    {
        $client->request('GET', '/logout');
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }
}