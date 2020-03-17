<?php

namespace Kikwik\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Throwable;

class AccountDisabledException extends AccountStatusException
{
    public function getMessageKey()
    {
        return 'User access is disabled.';
    }

}