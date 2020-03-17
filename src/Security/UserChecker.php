<?php

namespace Kikwik\UserBundle\Security;


use Kikwik\UserBundle\Exception\AccountDisabledException;
use Kikwik\UserBundle\Model\BaseUser;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof BaseUser) {
            return;
        }

        if (!$user->isEnabled()) {
            throw new AccountDisabledException();
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        // nothing to do here
    }

}