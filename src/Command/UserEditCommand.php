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

class UserEditCommand extends BaseCommand
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
            ->setName('kikwik:user:edit')
            ->setDescription('Edit a user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The user unique identifier'),
                new InputOption('roles', null, InputOption::VALUE_OPTIONAL, 'The new roles (optional, comma separated)'),
                new InputOption('password', null, InputOption::VALUE_OPTIONAL, 'The new password (optional)'),
            ));
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Delete a '.$this->userClass);

        $this->askForUsernameArgument($input, $output, true);
        $this->askForRolesOption($input, $output);
        $this->askForPasswordOption($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $newPassword = $input->getOption('password');
        $newRoles = $input->getOption('roles');

        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userIdentifierField => $username]);

        if($newPassword)
        {
            $user->setPassword($this->passwordEncoder->encodePassword($user,$newPassword));
        }

        if($newRoles)
        {
            $roles = explode(',',str_replace(' ','',$newRoles));
            $roles = array_diff($roles, ['ROLE_USER']);
            $user->setRoles($roles);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User '.$username.' successfully edited'."\n".'roles: '.implode(', ',$user->getRoles()));
    }
}