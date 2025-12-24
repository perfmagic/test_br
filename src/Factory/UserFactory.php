<?php

namespace App\Factory;

use App\Dto\UserDto;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function createFromDto(UserDto $dto): User
    {
        $user = new User();
        $this->updateFromDto($user, $dto);
        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    public function updateFromDto(User $user, UserDto $dto): void
    {
        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);

        if ($dto->password) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $dto->password
            );
            $user->setPassword($hashedPassword);
        }
    }
}
