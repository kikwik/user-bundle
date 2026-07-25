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
        $this->doLogin($client);


        // refresh user and check data after first login
        $user = UserFactory::repository()->first();
        $this->assertEquals(1, $user->getLoginCount());
        $this->assertNotNull($user->getLastLoginAt());
        $this->assertEquals('127.0.0.1', $user->getLastLoginFromIp());
        $this->assertNull($user->getPreviousLoginAt());
        $this->assertNull($user->getPreviousLoginFromIp());

        // logout and second login
        $this->doLogout($client);
        sleep(1);
        $this->doLogin($client);

        // refresh user and check data after second login
        $user = UserFactory::repository()->first();
        $this->assertEquals(2, $user->getLoginCount());
        $this->assertNotNull($user->getLastLoginAt());
        $this->assertEquals('127.0.0.1', $user->getLastLoginFromIp());
        $this->assertNotNull($user->getPreviousLoginAt());
        $this->assertEquals('127.0.0.1', $user->getLastLoginFromIp());

        $this->assertLessThan($user->getLastLoginAt(), $user->getPreviousLoginAt());
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