<?php

namespace Kikwik\UserBundle\Form;


use Kikwik\UserBundle\Validator\Constraints\Password;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('newPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'required' => true,
                'invalid_message' => 'kikwik_user.new_password.mismatch',
                'first_options' => ['label' => 'change_password.form.new_password', 'attr' => ['autocomplete' => 'off']],
                'second_options' => ['label' => 'change_password.form.new_password_confirmation'],
                'constraints' => new Password(['min'=>$options['password_min_length']]),
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'KikwikUserBundle',
            'password_min_length' => 8,
            'attr' => ['data-test'=>'change-password-form'],
        ]);
    }


}