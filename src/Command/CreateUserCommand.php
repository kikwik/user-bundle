<?php

namespace Kikwik\UserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateUserCommand extends Command
{
    /**
     * @var string
     */
    private $userClass;

    /**
     * @var string
     */
    private $userIdentifierField;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface
     */
    private $passwordEncoder;


    public function __construct(string $userClass, string $userIdentifierField, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct();
        $this->userClass = $userClass;
        $this->entityManager = $entityManager;
        $this->userIdentifierField = $userIdentifierField;
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

        if (!$input->getArgument('username'))
        {
            $input->setArgument('username', $io->ask('Please choose a username ('.$this->userIdentifierField.')',null,function ($value){
                if (!$value) {
                    throw new \RuntimeException('Username can not be empty');
                }

                return (string) $value;
            }));
        }
        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userIdentifierField => $input->getArgument('username')]);
        if($user)
        {
            throw new \RuntimeException('User '.$input->getArgument('username').' already exists');
        }


        if (!$input->getArgument('password'))
        {
            $input->setArgument('password', $io->ask('Please choose a password',null,function ($value){
                if (!$value) {
                    throw new \RuntimeException('Password can not be empty');
                }

                return (string) $value;
            }));
        }

        if(!$input->getOption('super-admin'))
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