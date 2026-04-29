<?php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // SymfonyStyle lets you create a neat console interface
        // with interactive prompts
        $io = new SymfonyStyle($input, $output);
    
        // Ask the user for email and password
        $email = $io->ask('Email');
        $username = $io->ask('Username');
        $password = $io->askHidden('Password');

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success('User created: ' . $email);

        return Command::SUCCESS;
    }
}