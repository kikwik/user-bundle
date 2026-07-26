<?php

namespace Kikwik\UserBundle\Tests\Integration\Controller;

use Kikwik\UserBundle\Tests\BaseWebTestCase;
use Kikwik\UserBundle\Tests\Factory\UserFactory;

class PasswordControllerTest extends BaseWebTestCase
{
    public function testChangePasswordForAnonimousUsersRedirectToLogin()
    {
        // visit change password page as anonimous user
        $client = $this->getTestClient();
        $client->request('GET', '/password/change');

        // anonimuos user is redirected to login page
        self::assertResponseRedirects('/login');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="test-login-form"]');
    }

    public function testChangePasswordForAuthenticatedUsers()
    {
        // create a user
        $user = UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
        ]);
        // check that passwordChagedAt and passwordChangedFromIp are null
        self::assertNull($user->getPasswordChangedAt());
        self::assertNull($user->getPasswordChangedFromIp());

        // do login and visit the change password page
        $client = $this->getTestClient();
        $this->doLogin($client, 'mario', 'password');
        $client->request('GET', '/password/change');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="change-password-form"]');

        // submit the form
        $beforeChangeTimestamp = time();
        $client->submitForm('change-password-submit', [
            'change_password_form[newPassword][first]' => 'NuovaPassword123!',
            'change_password_form[newPassword][second]' => 'NuovaPassword123!',
        ]);
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="flash"]', 'The password has been changed.');
        $afterChangeTimestamp = time();

        // do logout and login with new password
        $this->doLogout($client);
        $this->doLogin($client, 'mario', 'NuovaPassword123!');

        // refresh the user
        $user = UserFactory::repository()->first();
        // check passwordChangedAt
        self::assertNotNull($user->getPasswordChangedAt());
        self::assertGreaterThanOrEqual($beforeChangeTimestamp, $user->getPasswordChangedAt()->getTimestamp());
        self::assertLessThanOrEqual($afterChangeTimestamp, $user->getPasswordChangedAt()->getTimestamp());
        // check passwordChangedFromIp
        self::assertEquals('127.0.0.1', $user->getPasswordChangedFromIp());
    }

    public function testRequestPasswordForAuthenticatedUsersRedirectToChangePassword()
    {
        // create a user
        UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
        ]);

        // do login and visit the request password page
        $client = $this->getTestClient();
        $this->doLogin($client, 'mario', 'password');
        $client->request('GET', '/password/request');

        // user is redirected to change password page
        self::assertResponseRedirects('/password/change');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="change-password-form"]');
    }

    public function testRequestPasswordForAnonimousUsersWithEmail()
    {
        // create a user with email
        $user = UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
            'email' => 'mario@example.com'
        ]);
        self::assertNull($user->getChangePasswordSecret());
        self::assertNull($this->getPropertyValue($user,'changePasswordRequestedAt'));

        // visit request password page as anonimous user
        $client = $this->getTestClient();
        $client->request('GET', '/password/request');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="request-password-form"]');

        // submit the form
        $beforeRequestTimestamp = time();
        $client->submitForm('request-password-submit', [
            'request_password_form[userIdentifier]' => 'mario',
        ]);
        // check an email is sent
        self::assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailHeaderSame($email, 'To', 'mario@example.com');
        self::assertEmailTextBodyContains($email, sprintf('/password/reset/mario/%s', $user->getChangePasswordSecret()));
        // follow redirect
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="flash"]', 'We have sent you an email with instructions on how to modify your password.');
        $afterRequestTimestamp = time();

        // refresh the user
        $user = UserFactory::repository()->first();
        // check passwordSecret
        self::assertNotNull($user->getChangePasswordSecret());
        // check changePasswordRequestedAt
        $changePasswordRequestedAt = $this->getPropertyValue($user,'changePasswordRequestedAt');
        self::assertNotNull($changePasswordRequestedAt);
        self::assertGreaterThanOrEqual($beforeRequestTimestamp, $changePasswordRequestedAt->getTimestamp());
        self::assertLessThanOrEqual($afterRequestTimestamp, $changePasswordRequestedAt->getTimestamp());

    }

    public function testRequestPasswordForAnonimousUsersWithoutEmail()
    {
        // create a user without email
        $user = UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
            'email' => null
        ]);

        // visit request password page as anonimous user
        $client = $this->getTestClient();
        $client->request('GET', '/password/request');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="request-password-form"]');

        // submit the form
        $client->submitForm('request-password-submit', [
            'request_password_form[userIdentifier]' => 'mario',
        ]);
        // check that no email is sent
        self::assertEmailCount(0);
        // follow redirect
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="flash"]', 'Email address not found.');
    }

    public function testRequestPasswordForAnonimousUsersWithInvalidEmail()
    {
        // create a user with invalid email
        $user = UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
            'email' => 'not-an-email'
        ]);

        // visit request password page as anonimous user
        $client = $this->getTestClient();
        $client->request('GET', '/password/request');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="request-password-form"]');

        // submit the form
        $client->submitForm('request-password-submit', [
            'request_password_form[userIdentifier]' => 'mario',
        ]);
        // check that no email is sent
        self::assertEmailCount(0);
        // follow redirect
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-test="flash"]', 'The email address associated with your account is not valid.');
    }

    public function testResetPassword()
    {
        // create a user with a change password secret
        $user = UserFactory::createOne([
            'username' => 'mario',
            'password' => 'password',
        ]);
        $user->generateChangePasswordSecret();
        $user->_save();
        self::assertNotNull($user->getChangePasswordSecret());

        // visit reset password page as anonimous user
        $client = $this->getTestClient();
        $client->request('GET', sprintf('/password/reset/mario/%s', $user->getChangePasswordSecret()));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-test="change-password-form"]');

        // submit the form
        $beforeChangeTimestamp = time();
        $client->submitForm('reset-password-submit', [
            'change_password_form[newPassword][first]' => 'NuovaPassword123!',
            'change_password_form[newPassword][second]' => 'NuovaPassword123!',
        ]);
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        $afterChangeTimestamp = time();

        // do login with new password
        $this->doLogin($client, 'mario', 'NuovaPassword123!');

        // refresh the user
        $user = UserFactory::repository()->first();
        // check passwordChangedAt
        self::assertNotNull($user->getPasswordChangedAt());
        self::assertGreaterThanOrEqual($beforeChangeTimestamp, $user->getPasswordChangedAt()->getTimestamp());
        self::assertLessThanOrEqual($afterChangeTimestamp, $user->getPasswordChangedAt()->getTimestamp());
        // check passwordChangedFromIp
        self::assertEquals('127.0.0.1', $user->getPasswordChangedFromIp());
    }
}