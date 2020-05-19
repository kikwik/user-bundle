<?php

namespace Kikwik\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Password) {
            throw new UnexpectedTypeException($constraint, Password::class);
        }

        if (null === $value || '' === $value) {
            $this->context->buildViolation($constraint->notBlankMessage)
                ->addViolation();
        }

        if(strlen($value)<$constraint->min)
        {
            $this->context->buildViolation($constraint->tooShortMessage)
                ->setParameter('{{ limit }}', $constraint->min)
                ->addViolation();
        }

    }

}