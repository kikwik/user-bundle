<?php

namespace Kikwik\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @Annotation
 */
class Password extends Constraint
{
    public $notBlankMessage = 'kikwik_user.new_password.blank';
    public $tooShortMessage = 'kikwik_user.new_password.short';

    public $min;

    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if (null === $this->min) {
            throw new MissingOptionsException(sprintf('Option "min" must be given for constraint "%s".', __CLASS__), ['min']);
        }
    }
}