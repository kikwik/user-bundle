<?php

namespace Kikwik\UserBundle\Tests\Integration\Controller;

use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Kikwik\UserBundle\Tests\Factory\UserFactory;

class PasswordControllerTest extends BaseWebTestCase
{
    public function testChangePasswordDeniedForAnonimousUsers()
    {
        $client = $this->getTestClient();
        $client->request('GET', '/password/change');

        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-test="test-login-form"]');
    }

    public function testChangePasswordForAuthenticatedUsers()
    {
        // create a user
        $user = UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
        ]);
        $this->assertNull($user->getPasswordChangedAt());
        $this->assertNull($user->getPasswordChangedFromIp());

        // do login and go to change password page
        $client = $this->getTestClient();
        $this->doLogin($client, 'mario', 'password');
        $client->request('GET', '/password/change');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-test="change-password-form"]');

        // submit the form
        $beforeChangeTimestamp = time();
        $client->submitForm('change-password-submit', [
            'change_password_form[newPassword][first]' => 'NuovaPassword123!',
            'change_password_form[newPassword][second]' => 'NuovaPassword123!',
        ]);
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $afterChangeTimestamp = time();

        // do logout and login with new password
        $this->doLogout($client);
        $this->doLogin($client, 'mario', 'NuovaPassword123!');

        // refresh the user
        $user = UserFactory::repository()->first();
        // check passwordChangedAt
        $this->assertNotNull($user->getPasswordChangedAt());
        $this->assertGreaterThanOrEqual($beforeChangeTimestamp, $user->getPasswordChangedAt()->getTimestamp());
        $this->assertLessThanOrEqual($afterChangeTimestamp, $user->getPasswordChangedAt()->getTimestamp());
        // check passwordChangedFromIp
        $this->assertEquals('127.0.0.1', $user->getPasswordChangedFromIp());
    }

    public function testRequestPasswordForAnonimousUsers()
    {
        $this->markTestSkipped();
    }

    public function testRequestPasswordForAuthenticatedUsers()
    {
        $this->markTestSkipped();
    }

    public function testResetPassword()
    {
        $this->markTestSkipped();
    }
}