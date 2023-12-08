<?php

namespace Kikwik\UserBundle\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'security.interactive_login' => 'onLogin'
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        /** @var \Kikwik\UserBundle\Model\BaseUser $user */
        $user = $event->getAuthenticationToken()->getUser();
        $user->newLogin();

        $this->em->persist($user);
        $this->em->flush();
    }

}