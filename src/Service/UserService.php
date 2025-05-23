<?php

namespace App\Service;

use App\Dto\UserDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {}

    public function createUser(UserDto $dto): User
    {
        $user = new User();
        $this->hydrateUserFromDto($user, $dto, true);

        if ($dto->role) {
            $user->setRoles([$dto->role]);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function updateUser(int $id, UserDto $dto): User
    {
        $user = $this->getUser($id);
        $this->hydrateUserFromDto($user, $dto, false);

        if ($dto->role) {
            $user->setRoles([$dto->role]);
        }

        $this->em->flush();

        return $user;
    }

    public function deleteUser(int $id): void
    {
        $user = $this->getUser($id);
        $this->em->remove($user);
        $this->em->flush();
    }

    public function getUser(int $id): User
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new NotFoundHttpException("Utilisateur non trouvé.");
        }

        return $user;
    }

    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function createDtoFromEntity(User $user): UserDto
    {
        $dto = new UserDto();
        $dto->firstname = $user->getFirstname();
        $dto->lastname = $user->getLastname();
        $dto->email = $user->getEmail();
        $dto->genre = $user->getGenre() === 'H' ? 'M' : 'F';
        $dto->rgpd = $user->getRgpd();
        $dto->encrypte = null;

        return $dto;
    }

    private function hydrateUserFromDto(User $user, UserDto $dto, bool $isCreate): void
    {
        $user->setFirstName($dto->firstname)
             ->setLastName($dto->lastname)
             ->setEmail($dto->email)
             ->setGenre($dto->genre === 'M' ? 'H' : 'F')
             ->setRgpd($dto->rgpd);

        if ($isCreate || !empty($dto->encrypte)) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $dto->encrypte)
            );
        }
    }

    public function userExists(int $id): bool
    {
        return $this->userRepository->find($id) !== null;
    }
}
