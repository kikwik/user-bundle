<?php

namespace Kikwik\UserBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Kikwik\UserBundle\Form\ChangePasswordFormType;
use Kikwik\UserBundle\Form\RequestPasswordFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class PasswordController
{

    private $entityManager;
    private $authorizationChecker;
    private $formFactory;
    private $twig;
    private $tokenStorage;
    private $urlGenerator;
    private $session;
    private $passwordHasher;
    private $translator;

    private $mailer;
    private $userClass;
    private $userIdentifierField;
    private $userEmailField;
    private $passwordMinLength;
    private $senderEmail;
    private $senderName;


    public function __construct(
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        FormFactoryInterface $formFactory,
        Environment $twig,
        UsageTrackingTokenStorage $tokenStorage,
        UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        MailerInterface $mailer,
        string $userClass, string $userIdentifierField, ?string $userEmailField, int $passwordMinLength, ?string $senderEmail, ?string $senderName)
    {
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->formFactory = $formFactory;
        $this->twig = $twig;
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
        $this->session = $requestStack->getSession();
        $this->passwordHasher = $passwordHasher;
        $this->translator = $translator;
        $this->mailer = $mailer;

        $this->userClass = $userClass;
        $this->userIdentifierField = $userIdentifierField;
        $this->userEmailField = $userEmailField;
        $this->passwordMinLength = $passwordMinLength;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }


    public function changePassword(Request $request, SessionInterface $session)
    {
        // check permission
        $this->denyAccessUnlessGranted('ROLE_USER');

        // save referer
        $this->saveReferer($request, $session);

        // create and submit form
        $form = $this->createForm(ChangePasswordFormType::class, null, ['password_min_length'=>$this->passwordMinLength]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $newPassword = $data['newPassword'];

            $user = $this->getUser();
            $user->setPassword($this->passwordHasher->hashPassword($user,$newPassword));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->session->getFlashBag()->add('success change_password',$this->translator->trans('change_password.flash.success',[],'KikwikUserBundle'));
            $returnUrl = $this->removeReferer($session);
            return new RedirectResponse($returnUrl);
        }

        return $this->render('@KikwikUser/changePassword.html.twig', [
            'form' => $form->createView()
        ]);

    }

    public function requestPassword(Request $request, SessionInterface $session)
    {
        // check permission
        if($this->isGranted('ROLE_USER'))
        {
            return $this->redirect($this->urlGenerator->generate('kikwik_user_password_change'));
        }

        $askForEmail = $this->userIdentifierField == $this->userEmailField;

        // save referer
        $this->saveReferer($request, $session);

        // create and submit form
        $form = $this->createForm(RequestPasswordFormType::class,null,['askForEmail'=>$askForEmail]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $userIdentifier = $data['userIdentifier'];

            /** @var \Kikwik\UserBundle\Model\BaseUser $user */
            $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userIdentifierField => $userIdentifier]);
            if($user)
            {
                // generate and save a secret code
                $secret = $user->generateChangePasswordSecret();
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // check email configuration
                if(!$this->userEmailField)
                {
                    $this->session->getFlashBag()->add('danger',$this->translator->trans('request_password.flash.danger_no_email_configuration',[],'KikwikUserBundle'));
                    return $this->redirectToRoute('kikwik_user_password_request');
                }

                // check user email
                $emailGetter = 'get'.ucfirst($this->userEmailField);
                $userEmail = $user->$emailGetter();
                if(!$userEmail)
                {
                    $this->session->getFlashBag()->add('danger',$this->translator->trans('request_password.flash.danger_no_email',[],'KikwikUserBundle'));
                    return $this->redirectToRoute('kikwik_user_password_request');
                }

                try {
                    $recipient = new Address($userEmail);
                }
                catch (\Exception $e)
                {
                    $this->session->getFlashBag()->add('danger',$this->translator->trans('request_password.flash.danger_email_not_valid',[],'KikwikUserBundle'));
                    return $this->redirectToRoute('kikwik_user_password_request');
                }

                // send email
                if($this->senderEmail)
                {
                    $fromAddress = New Address($this->senderEmail, $this->senderName);
                }
                else
                {
                    $fromAddress = New Address($this->translator->trans('request_password.email.sender',[],'KikwikUserBundle'));
                }
                $email = new TemplatedEmail();
                $email
                    ->from($fromAddress)
                    ->to($userEmail)
                    ->subject($this->translator->trans('request_password.email.subject',[],'KikwikUserBundle'))
                    ->htmlTemplate('@KikwikUser/email/requestPassword.html.twig')
                    ->context([
                        'reset_url' => $this->urlGenerator->generate('kikwik_user_password_reset',['userIdentifier'=>$userIdentifier,'secretCode'=>$secret],UrlGeneratorInterface::ABSOLUTE_URL),
                        'username' => $userIdentifier,
                    ])
                ;
                $this->mailer->send($email);


                $this->session->getFlashBag()->add('success request_password',$this->translator->trans('request_password.flash.success',[],'KikwikUserBundle'));
                return $this->redirectToRoute('kikwik_user_password_request');
            }
            else
            {
                $this->session->getFlashBag()->add('danger',$this->translator->trans('request_password.flash.danger_no_user',[],'KikwikUserBundle'));
                return $this->redirectToRoute('kikwik_user_password_request');
            }
        }

        return $this->render('@KikwikUser/requestPassword.html.twig', [
            'form' => $form->createView(),
            'askForEmail' => $askForEmail,
        ]);
    }

    public function resetPassword(string $userIdentifier, string $secretCode, Request $request, SessionInterface $session)
    {
        // save referer
        $this->saveReferer($request, $session);

        // find the user
        /** @var \Kikwik\UserBundle\Model\BaseUser $user */
        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([
            $this->userIdentifierField => $userIdentifier,
            'changePasswordSecret' => $secretCode,
        ]);

        // create form
        $form = $this->createForm(ChangePasswordFormType::class, null, ['password_min_length'=>$this->passwordMinLength]);

        if($user)
        {
            // submit form
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid())
            {
                $data = $form->getData();
                $newPassword = $data['newPassword'];

                $user->setPassword($this->passwordHasher->hashPassword($user,$newPassword));
                $user->removeChangePasswordSecret();

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->session->getFlashBag()->add('success reset_password',$this->translator->trans('reset_password.flash.success',[],'KikwikUserBundle'));
                $returnUrl = $this->removeReferer($session);
                return new RedirectResponse($returnUrl);
            }
        }
        return $this->render('@KikwikUser/resetPassword.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    private function removeReferer(SessionInterface $session)
    {
        return $session->remove('kikwik_user.password.referer');
    }

    private function saveReferer(Request $request, SessionInterface $session)
    {
        if(!$session->has('kikwik_user.password.referer'))
        {
            $forbiddenReferers = [
                $this->urlGenerator->generate('kikwik_user_password_change',[],UrlGeneratorInterface::ABSOLUTE_URL),
                $this->urlGenerator->generate('kikwik_user_password_request',[],UrlGeneratorInterface::ABSOLUTE_URL),
            ];

            $currentReferer = $request->headers->get('referer');

            if($currentReferer && !in_array($currentReferer,$forbiddenReferers))
            {
                $session->set('kikwik_user.password.referer', $currentReferer);
            }
            else
            {
                $session->set('kikwik_user.password.referer', '/');
            }
        }
    }




    /**************************************/
    /* AbstractController helpers         */
    /**************************************/

    protected function isGranted($attribute, $subject = null): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $subject);
    }

    protected function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create($type, $data, $options);
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $content = $this->twig->render($view, $parameters);

        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($content);

        return $response;
    }

    protected function getUser(): ?UserInterface
    {
        if (null === $token = $this->tokenStorage->getToken()) {
            return null;
        }

        return $token->getUser();
    }

    protected function denyAccessUnlessGranted($attribute, $subject = null, string $message = 'Access Denied.'): void
    {
        if (!$this->isGranted($attribute, $subject)) {
            $exception = new AccessDeniedException($message);
            $exception->setAttributes($attribute);
            $exception->setSubject($subject);

            throw $exception;
        }
    }

    /**
     * Returns a RedirectResponse to the given URL.
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->urlGenerator->generate($route, $parameters), $status);
    }
}