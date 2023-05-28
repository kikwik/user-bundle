<?php

namespace Kikwik\UserBundle\Behat;

use Behat\Mink\Exception\ExpectationException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;

trait KikwikUserContextTrait
{
    protected function getUserClass()
    {
        return 'App\Entity\User';
    }

    /**
     * @Given There is a user :userIdentifier with password :userPassword and :userRoles roles
     */
    public function thereIsAUserWithPasswordAndRole($userIdentifier, $userPassword, $userRoles)
    {
        $userClass = $this->getUserClass();
        $user = $this->entityManager->getRepository($userClass)->findOneByEmail($userIdentifier);
        if(!$user)
        {
            $user = new $userClass();
            $user->setEmail($userIdentifier);
        }
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
        $this->visitPath('/login');
        $this->getSession()->getPage()->fillField('Email', $userIdentifier);
        $this->getSession()->getPage()->fillField('Password', $userPassword);
        $this->getSession()->getPage()->pressButton('Accedi');
        $this->assertPageNotContainsText('Credenziali non valide.');
    }


    /**
     * @When I follow the password reset link for user :email
     */
    public function iFollowThePasswordResetLinkForUser($email)
    {
        $userClass = $this->getUserClass();

        $user = $this->entityManager->getRepository($userClass)->findOneBy(['email'=>$email]);
        $this->entityManager->refresh($user);
        if(!$user)
        {
            $message = 'User with email "'.$email.'" not found';
            throw new ExpectationException($message, $this->getSession()->getDriver());
        }
        $this->visit(sprintf('/password/reset/%s/%s',$email, $user->getChangePasswordSecret()));
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
            throw new ExpectationException('No POST token found in the symfony profiler, did you forget to activate the "framework: profiler: { collect: true }" setting for the test environment?', $this->getSession()->getDriver());
        }
        $profilerInstance = $profiler->loadProfile($tokens[0]['token']);

        // Get the swiftmail collector
        if(!$profilerInstance->hasCollector('mailer'))
        {
            throw new ExpectationException('"mailer" collector not found in symfony profiler', $this->getSession()->getDriver());
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
                if($message->getHtmlTemplate() == '@KikwikUser/email/requestPassword.html.twig')
                {
                    $emailFound = true;
                }
            }
        }
        if(!$emailFound)
        {
            throw new ExpectationException('requestPassword email was not send', $this->getSession()->getDriver());
        }
    }



}