<?php


namespace Kikwik\UserBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ChangeUserPasswordCommand extends Command
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
            ->setName('kikwik:user:change-password')
            ->setDescription('Create a user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The user unique identifier'),
                new InputArgument('password', InputArgument::REQUIRED, 'The new password'),
            ));
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Change password for an existing '.$this->userClass);


        if (!$input->getArgument('username'))
        {
            $input->setArgument('username', $io->ask('Please choose an existing username ('.$this->userIdentifierField.')',null,function ($value){
                if (!$value) {
                    throw new \RuntimeException('Username can not be empty');
                }

                return (string) $value;
            }));
        }
        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userIdentifierField => $input->getArgument('username')]);
        if(!$user)
        {
            throw new \RuntimeException('User '.$input->getArgument('username').' does not exists');
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

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userIdentifierField => $username]);

        if(!$user)
        {
            $io->error('User '.$username.' does not exists');
        }
        else
        {
            // password
            $user->setPassword($this->passwordEncoder->encodePassword($user,$password));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success('Password for "'.$username.'" successfully changed');
        }

        return 0;
    }
}