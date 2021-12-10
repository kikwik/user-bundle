<?php

namespace Kikwik\UserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserCreateCommand extends BaseCommand
{
    /**
     * @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface
     */
    private $passwordEncoder;


    public function __construct(string $userClass, string $userIdentifierField, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($userClass, $userIdentifierField, $entityManager);
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure()
    {
        $this
            ->setName('kikwik:user:create')
            ->setDescription('Create a user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The user unique identifier'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                new InputOption('super-admin', null, InputOption::VALUE_NONE, 'Set the user as super admin'),
            ));
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating a new '.$this->userClass);

        $askForSuperAdmin = !$input->getArgument('password') && !$input->getOption('super-admin');

        $this->askForUsernameArgument($input, $output, false);
        $this->askForPasswordArgument($input, $output, false);

        if($askForSuperAdmin)
        {
            $superAdminQuestion = new ConfirmationQuestion('Is this a super admin user?', false);
            $input->setOption('super-admin', $io->askQuestion($superAdminQuestion));
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $superadmin = $input->getOption('super-admin');


        $user = new $this->userClass();

        // username
        $usernameSetter = 'set'.ucfirst($this->userIdentifierField);
        $user->$usernameSetter($username);

        // password
        $user->setPassword($this->passwordEncoder->encodePassword($user,$password));

        // super-admin
        if($superadmin)
        {
            $user->setRoles(['ROLE_SUPER_ADMIN']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User '.$username.' successfully created');

        return 0;
    }


}