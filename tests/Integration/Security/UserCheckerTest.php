<?php

namespace Kikwik\UserBundle\Tests\Integration\Security;

use Kikwik\UserBundle\Exception\AccountDisabledException;
use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Kikwik\UserBundle\Tests\Factory\UserFactory;
use Symfony\Bundle\SecurityBundle\Security;

class UserCheckerTest extends BaseWebTestCase
{
    public function testUserEnabled()
    {
        UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
            'isEnabled' => true,
        ]);

        $client = $this->getTestClient();
        $this->doLogin($client, 'mario', 'password');
    }

    public function testUserNotEnabled()
    {
        UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
            'isEnabled' => false,
        ]);

        $client = $this->getTestClient();
        $crawler = $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Login')->form([
            '_username' => 'mario',
            '_password' => 'password',
        ]);
        $client->submit($form);
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Invalid credentials');
    }
}