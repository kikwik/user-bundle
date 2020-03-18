<?php

namespace Kikwik\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('newPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'required' => false,
                'invalid_message' => 'kikwik_user.new_password.mismatch',
                'first_options' => ['label' => 'form.new_password', 'attr' => ['autocomplete' => 'off']],
                'second_options' => ['label' => 'form.new_password_confirmation'],
                'constraints' => [ new NotBlank(['message'=>'kikwik_user.new_password.blank'])]
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'KikwikUserBundle',
        ]);
    }


}