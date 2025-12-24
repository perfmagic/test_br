<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user.',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('login', InputArgument::REQUIRED, 'The login of the user.')
            ->addArgument('pass', InputArgument::REQUIRED, 'The pass of the user.')
            ->addArgument('phone', InputArgument::REQUIRED, 'The phone number of the user.')
            ->addOption('root', null, InputOption::VALUE_NONE, 'Set user as a root user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = new User();
        $user->setLogin($input->getArgument('login'));
        $user->setPhone($input->getArgument('phone'));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $io->error((string) $errors);
            return Command::FAILURE;
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $input->getArgument('pass')
        );
        $user->setPassword($hashedPassword);

        $roles = ['ROLE_USER'];
        if ($input->getOption('root')) {
            $roles[] = 'ROLE_ROOT';
        }
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('User %s created successfully.', $user->getLogin()));

        return Command::SUCCESS;
    }
}
