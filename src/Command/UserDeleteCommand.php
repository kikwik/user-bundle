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

class UserDeleteCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('kikwik:user:delete')
            ->setDescription('Delete a user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The user unique identifier'),
            ));
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Delete a '.$this->userClass);

        $this->askForUsernameArgument($input, $output, true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $user = $this->entityManager->getRepository($this->userClass)->findOneBy([$this->userIdentifierField => $username]);
        if(!$user)
        {
            throw new \RuntimeException('User '.$username.' does not exists');
        }

        if($io->askQuestion(new ConfirmationQuestion('Are you sure?', false)))
        {
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $io->success('User '.$username.' successfully deleted');
        }
        else
        {
            $io->warning('User '.$username.' was NOT deleted');
        }

        return 0;
    }
}