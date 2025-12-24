<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Assert\Length(
        max: 8,
        groups: ['create', 'update']
    )]
    public ?string $login = null;

    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Assert\Length(
        max: 8,
        groups: ['create', 'update']
    )]
    public ?string $phone = null;

    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Assert\Length(
        max: 8,
        groups: ['create', 'update']
    )]
    public ?string $password = null;
}
