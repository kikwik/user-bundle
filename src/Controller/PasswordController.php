<?php

namespace Kikwik\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Kikwik\UserBundle\Form\ChangePasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordController extends AbstractController
{

    private $userClass;

    private $userIdentifierField;

    private $entityManager;

    private $passwordEncoder;

    private $translator;

    public function __construct(string $userClass, string $userIdentifierField, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $this->userClass = $userClass;
        $this->userIdentifierField = $userIdentifierField;
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->translator = $translator;
    }

    public function changePassword(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(ChangePasswordFormType::class);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $newPassword = $data['newPassword'];

            $user = $this->getUser();
            $user->setPassword($this->passwordEncoder->encodePassword($user,$newPassword));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success',$this->translator->trans('change_password.flash.success',[],'KikwikUserBundle'));
            $url = $this->generateUrl('kikwik_user_bundle_password_change');
            return new RedirectResponse($url);
        }

        return $this->render('@KikwikUser/changePassword.html.twig', [
            'form' => $form->createView()
        ]);

    }

    public function requestPassword()
    {

    }

    public function resetPassword()
    {

    }
}