<?php


namespace Kikwik\UserBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ChangeUserRolesCommand extends Command
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

    public function __construct(string $userClass, string $userIdentifierField, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->userClass = $userClass;
        $this->userIdentifierField = $userIdentifierField;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setName('kikwik:user:change-roles')
            ->setDescription('Change roles to an existing user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The user unique identifier'),
                new InputOption('addRole', null, InputOption::VALUE_OPTIONAL, 'The role to add'),
                new InputOption('removeRole', null, InputOption::VALUE_OPTIONAL, 'The role to remove'),
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

        if(!$input->getOption('addRole'))
        {
            $input->setOption('addRole', $io->ask('Enter the role to add',''));
        }

        if(!$input->getOption('removeRole'))
        {
            $input->setOption('removeRole', $io->ask('Enter the role to remove',''));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userIdentifierField => $username]);

        $roles = array_diff($user->getRoles(), ['ROLE_USER']);
        if($input->getOption('addRole'))
        {
            $roles[] = $input->getOption('addRole');
        }
        if($input->getOption('removeRole'))
        {
            $roles = array_diff($roles, [$input->getOption('removeRole')]);
        }
        $user->setRoles(array_values($roles));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Roles for "'.$username.'" successfully changed');
    }
}