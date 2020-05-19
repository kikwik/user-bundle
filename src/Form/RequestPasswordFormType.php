<?php

namespace Kikwik\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RequestPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userIdentifier', TextType::class, array(
                'required' => true,
                'label' => 'request_password.form.userIdentifier',
                'constraints' => [ new NotBlank(['message'=>'kikwik_user.userIdentifier.blank'])]
            ));
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'KikwikUserBundle',
        ]);
    }
}