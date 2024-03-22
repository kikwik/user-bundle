<?php

namespace Kikwik\UserBundle\Behat;

use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;

trait KikwikUserContextTrait
{
    protected function getUserClass()
    {
        return 'App\Entity\User';
    }
    
    protected function getUserIdentifierField()
    {
        return 'email';
    }

    protected function getLoginPath()
    {
        return '/login';
    }

    protected function getPasswordPath()
    {
        return '/password';
    }

    protected function getInvalidCredentialMessage()
    {
        return 'Credenziali non valide';
    }

    /**
     * @Given There is a user :userEmail with password :userPassword and :userRoles roles
     */
    public function thereIsAUserWithPasswordAndRole($userEmail, $userPassword, $userRoles)
    {
        $userClass = $this->getUserClass();
        $user = $this->entityManager->getRepository($userClass)->findOneByEmail($userEmail);
        if(!$user)
        {
            $user = new $userClass();
            $user->setEmail($userEmail);
        }
        $user->setPassword($this->passwordHasher->hashPassword($user,$userPassword));
        $user->setRoles(array_map('trim', explode(',', $userRoles)));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @Given There is a user :userIdentifier with email :userEmail and password :userPassword and :userRoles roles
     */
    public function thereIsAUserWithEmailAndPasswordAndRoles($userIdentifier, $userEmail, $userPassword, $userRoles)
    {
        $userClass = $this->getUserClass();
        $user = $this->entityManager->getRepository($userClass)->findOneByUsername($userIdentifier);
        if(!$user)
        {
            $user = new $userClass();
            $user->setUsername($userIdentifier);
        }
        $user->setEmail($userEmail);
        $user->setPassword($this->passwordHasher->hashPassword($user,$userPassword));
        $user->setRoles(array_map('trim', explode(',', $userRoles)));
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @Given I am authenticated as :userIdentifier with password :userPassword
     */
    public function iAmAuthenticatedAsWithPassword($userIdentifier, $userPassword)
    {
        $this->visitPath($this->getLoginPath());
        $this->getSession()->getPage()->fillField($this->getUserIdentifierField(), $userIdentifier);
        $this->getSession()->getPage()->fillField('password', $userPassword);
        $this->getSession()->getPage()->pressButton('login-submit');
        $this->assertPageNotContainsText($this->getInvalidCredentialMessage());
    }

    /**
     * @When user :userIdentifier is disabled
     */
    public function userIsDisabled($userIdentifier)
    {
        $userClass = $this->getUserClass();
        $user = $this->entityManager->getRepository($userClass)->findOneBy([$this->getUserIdentifierField()=>$userIdentifier]);
        if(!$user)
        {
            $message = 'User "'.$userIdentifier.'" not found';
            throw new ExpectationFailedException($message);
        }
        $user->setIsEnabled(false);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }


    /**
     * @When I follow the password reset link for user :userIdentifier
     */
    public function iFollowThePasswordResetLinkForUser($userIdentifier)
    {
        $userClass = $this->getUserClass();

        $user = $this->entityManager->getRepository($userClass)->findOneBy([$this->getUserIdentifierField()=>$userIdentifier]);
        if(!$user)
        {
            $message = 'User "'.$userIdentifier.'" not found';
            throw new ExpectationFailedException($message);
        }
        $this->entityManager->refresh($user);
        $this->visit(sprintf($this->getPasswordPath().'/reset/%s/%s',$userIdentifier, $user->getChangePasswordSecret()));
    }

    protected function getSymfonyProfiler()
    {
        return $this->driverContainer->get('profiler');
    }

    /**
     * @Then the reset password mail was sent to :email
     */
    public function theResetPasswordMailWasSentTo($email)
    {
        $profiler = $this->getSymfonyProfiler();

        // Load the debug profile
        // Must be a POST request within the last 30 seconds
        $start = date('Y-m-d H:i:s', time() - 10);
        $end = date('Y-m-d H:i:s');

        $tokens = $profiler->find('','',1, 'POST', $start, $end);
        if(!isset($tokens[0]['token']))
        {
            throw new ExpectationFailedException('No POST token found in the symfony profiler, did you forget to activate the "framework: profiler: { collect: true }" setting for the test environment?');
        }
        $profilerInstance = $profiler->loadProfile($tokens[0]['token']);

        // Get the swiftmail collector
        if(!$profilerInstance->hasCollector('mailer'))
        {
            throw new ExpectationFailedException('"mailer" collector not found in symfony profiler');
        }
        /** @var MessageDataCollector $mailerCollector */
        $mailerCollector = $profilerInstance->getCollector('mailer');

        // Get all the available messages
        $messages = $mailerCollector->getEvents()->getMessages();

        $emailFound = false;
        foreach($messages as $message)
        {
            if($message instanceof TemplatedEmail)
            {
                if($message->getHeaders()->get('to')->getAddresses()[0]->getAddress() == $email)
                {
                    $emailFound = true;
                }
            }
        }
        if(!$emailFound)
        {
            throw new ExpectationFailedException(sprintf('requestPassword email was not sent to %s',$email));
        }
    }



}