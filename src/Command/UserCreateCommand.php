<?php

namespace Kikwik\UserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCreateCommand extends BaseCommand
{
    private $passwordHasher;


    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, string $userClass, string $userIdentifierField)
    {
        parent::__construct($entityManager, $userClass, $userIdentifierField);
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
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

    protected function interact(InputInterface $input, OutputInterface $output): void
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


    protected function execute(InputInterface $input, OutputInterface $output): int
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
        $user->setPassword($this->passwordHasher->hashPassword($user,$password));

        // super-admin
        if($superadmin)
        {
            $user->setRoles(['ROLE_SUPER_ADMIN']);
        }

        // set updatedBy and updatedBy
        $user->setCreatedBy('kikwik:user:create');
        $user->setUpdatedBy('kikwik:user:create');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User '.$username.' successfully created');

        return 0;
    }


}