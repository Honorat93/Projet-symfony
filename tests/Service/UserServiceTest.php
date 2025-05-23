<?php

namespace App\Tests\Service;

use App\Dto\UserDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private $em;
    private $hasher;
    private $repository;
    private $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->repository = $this->createMock(UserRepository::class);

        $this->service = new UserService(
            $this->em,
            $this->hasher,
            $this->repository
        );
    }

    public function testCreateUser(): void
    {
        $dto = new UserDto();
        $dto->firstname = 'Jean';
        $dto->lastname = 'Dupont';
        $dto->email = 'jean@example.com';
        $dto->genre = 'M';
        $dto->rgpd = true;
        $dto->encrypte = 'password123';
        $dto->role = 'ROLE_ADMIN';

        $this->hasher->method('hashPassword')->willReturn('hashed_password');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $user = $this->service->createUser($dto);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jean', $user->getFirstname());
        $this->assertEquals('ROLE_ADMIN', $user->getRoles()[0]);
    }

    public function testUpdateUser(): void
    {
        $dto = new UserDto();
        $dto->firstname = 'Jean';
        $dto->lastname = 'Dupont';
        $dto->email = 'jean@example.com';
        $dto->genre = 'M';
        $dto->rgpd = true;
        $dto->encrypte = 'newpass';
        $dto->role = 'ROLE_USER';

        $user = new User();

        $this->repository->method('find')->with(1)->willReturn($user);
        $this->hasher->method('hashPassword')->willReturn('hashed_pass');
        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->updateUser(1, $dto);

        $this->assertEquals('Jean', $updated->getFirstname());
        $this->assertEquals('ROLE_USER', $updated->getRoles()[0]);
    }

    public function testDeleteUser(): void
    {
        $user = new User();
        $this->repository->method('find')->willReturn($user);

        $this->em->expects($this->once())->method('remove')->with($user);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteUser(1);
        $this->assertTrue(true); // No exception = success
    }

    public function testDeleteUserNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->repository->method('find')->willReturn(null);

        $this->service->deleteUser(999);
    }

    public function testGetUser(): void
    {
        $user = new User();
        $this->repository->method('find')->willReturn($user);

        $result = $this->service->getUser(1);
        $this->assertInstanceOf(User::class, $result);
    }

    public function testGetUserNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->repository->method('find')->willReturn(null);

        $this->service->getUser(999);
    }

    public function testGetAllUsers(): void
    {
        $users = [new User(), new User()];
        $this->repository->method('findAll')->willReturn($users);

        $result = $this->service->getAllUsers();
        $this->assertCount(2, $result);
    }
    public function testUserExists(): void
    {
        $this->repository->method('find')->willReturn(new User());
        $this->assertTrue($this->service->userExists(1));

       
        $this->repository = $this->createMock(UserRepository::class);
        $this->repository->method('find')->willReturn(null);

        $this->service = new UserService($this->em, $this->hasher, $this->repository);

        $this->assertFalse($this->service->userExists(999));
    }


    public function testCreateDtoFromEntity(): void
    {
        $user = new User();
        $user->setFirstname('Alice')
             ->setLastname('Smith')
             ->setEmail('alice@example.com')
             ->setGenre('F')
             ->setRgpd(true);

        $dto = $this->service->createDtoFromEntity($user);

        $this->assertInstanceOf(UserDto::class, $dto);
        $this->assertEquals('Alice', $dto->firstname);
        $this->assertEquals('F', $dto->genre); 
    }
}
