KikwikUserBundle
=================

A super simple user bundle that provide very basic helpers for symfony 5.3 and 6.x user management.


Installation
------------

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kikwik/user-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Kikwik\UserBundle\KikwikUserBundle::class => ['all' => true],
];
```

### Step 3: Creating the User

Run `make:user` command:

```console
php bin/console make:user
```

Make your User class extends `Kikwik\UserBundle\Model\BaseUser`:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kikwik\UserBundle\Model\BaseUser;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends BaseUser implements UserInterface
{
    //...
}
```

Create the `config/packages/kikwik_user.yaml` config file, set the user class and the unique identifier field name of your user

```yaml
kikwik_user:
    user_class: App\Entity\User
    user_identifier_field: username
    user_email_field: email
    password_min_length: 8
    sender_email: '%env(SENDER_EMAIL)%'
    sender_name: '%env(SENDER_NAME)%'
    enable_admin: true  # default is true
```

and define sender vars in your .env file

```dotenv
###> kikwik/user-bundle ###
SENDER_EMAIL=no-reply@example.com
SENDER_NAME="My Company Name"
###< kikwik/user-bundle ###
```


Features
--------

### Disable user access ###

To activate the isEnabled feature set the user_checker option for your firewall in `config/packages/security.yaml`

```yaml
security:
    firewalls:
        main:
            pattern: ^/
            user_checker: Kikwik\UserBundle\Security\UserChecker
```

### Change password ###

To activate the change and forgot password feature add routes in `config/routes/kikwik_user.yaml`:

```yaml
kikwik_user_bundle_password:
    resource: '@KikwikUserBundle/Resources/config/routes.xml'
    prefix: '/password'
```

The forgot password uses symfony/mailer component, so you must configure it in `.env`

```
MAILER_DSN=sendmail+smtp://localhost
```

This will register the following route:

    * kikwik_user_password_change
    * kikwik_user_password_request
    * kikwik_user_password_reset

Copy translations file from `vendor/kikwik/user-bundle/src/Resources/translations/KikwikUserBundle.xx.yaml` 
to `translations/KikwikUserBundle.xx.yaml` and change at least the `request_password.email.sender` value 

```yaml
request_password:
    email:
        sender:  'no-reply@my-domain.ltd'
        subject: 'Istruzioni per reimpostare la password'
        content: |
            <p>
                Ciao {{ username }},<br/>
                Abbiamo ricevuto una richiesta per resettare la tua password,
                <a href="{{ reset_url }}">clicca qui per scegliere una nuova password</a><br/>
                oppure incolla in seguente link nella barra degli indirizzi del browser: <br/>{{ reset_url }}
            </p>
```


Behat
-----

Enable the profiler for the test environment in `config/packages/web_profiler.yaml`:

```yaml
when@test:
    framework:
        profiler: { collect: true }
```


Use the `KikwikUserContextTrait` in your behat context and initialize a `ContainerInterface $driverContainer` variable

```php
namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Kikwik\UserBundle\Behat\KikwikUserContextTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;


final class DemoContext extends MinkContextntext implements Context
{
    use KikwikUserContextTraittTrait;

    /** @var KernelInterface */
    private $kernel;
    
    /**
     * @var ContainerInterface
     */
    private $driverContainer;

    public function __construct(KernelInterface $kernel, ContainerInterface $driverContainer)
    {
        $this->kernel = $kernel;
        $this->driverContainer = $driverContainer;
    }
}
```

Create a feature file to test the reset password:

```yaml
Feature:
    In order to manage private access to site
    As a user
    I want to be able to reset password

    Background:
        Given There is a user with email "test@example.com"

    Scenario: Login page has the forgot password link
        When I go to "/login"
        Then I should see a "a[href='/password/request']" element

    Scenario: Request password
        # try a wrog login
        When I go to "/login"
        And I fill in "inputEmail" with "test@example.com"
        And I fill in "inputPassword" with "mySecterPassword"
        And I press "Sign in"
        Then I should see "Credenziali non valide."
        # request a new password
        When I go to "/password/request"
        Then I should see a "[data-test='request-password-form']" element
        When I fill in "request_password_form_userIdentifier" with "test@example.com"
        And I press "request-password-submit"
        Then I should see an ".alert.alert-success.request_password" element
        # check that email was sent
        And the reset password mail was sent to "test@example.com"
        # reset password
        When I follow the password reset link for user "test@example.com"
        Then I should see a "[data-test='change-password-form']" element
        When I fill in "change_password_form_newPassword_first" with "mySecterPassword"
        And I fill in "change_password_form_newPassword_second" with "mySecterPassword"
        And I press "reset-password-submit"
        Then I should see an ".alert.alert-success.reset_password" element
        # try the login
        When I go to "/login"
        And I fill in "inputEmail" with "test@example.com"
        And I fill in "inputPassword" with "mySecterPassword"
        And I press "Sign in"
        Then I should not see "Credenziali non valide."
```