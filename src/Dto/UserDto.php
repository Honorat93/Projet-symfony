<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\GroupSequence;
use App\Validator\UniqueUserEmail;

#[UniqueUserEmail(groups: ['create', 'Strict'])]
#[GroupSequence(['UserDto', 'Strict', 'create', 'admin'])]
class UserDto
{
    public ?int $id = null;

    #[Assert\NotBlank]
    public string $firstname = '';

    #[Assert\NotBlank]
    public string $lastname = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Choice(['M', 'F'], message: 'Le genre doit être M ou F')]
    public string $genre = '';

    #[Assert\NotNull]
    public bool $rgpd = false;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/',
        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et au moins 8 caractères.',
        groups: ['create', 'Strict']
    )]
    public ?string $encrypte = null;

    #[Assert\Choice(
        choices: ['ROLE_USER', 'ROLE_ADMIN'],
        message: 'Rôle invalide',
        groups: ['admin']
    )]
    public ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): void
    {
        $this->role = $role;
    }
}
