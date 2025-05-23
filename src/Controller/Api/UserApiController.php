<?php

namespace App\Controller\Api;

use App\Dto\UserDto;
use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[Route('/api')]
class UserApiController extends AbstractController
{
    #[Route('/register', name: 'api_create_user', methods: ['POST'])]
    public function createUser(
        #[MapRequestPayload] UserDto $userDto,
        UserService $userService
    ): JsonResponse {
        $user = $userService->createUser($userDto);

        return $this->json([
            'success' => true,
            'user_id' => $user->getId(),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/user/{id}', name: 'api_update_user', methods: ['PUT'])]
    public function updateUser(
        int $id,
        #[MapRequestPayload] UserDto $userDto,
        UserService $userService
    ): JsonResponse {
        $user = $userService->updateUser($id, $userDto);

        return $this->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour.',
            'user_id' => $user->getId(),
        ]);
    }

    #[Route('/user/{id}', name: 'api_delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, UserService $userService): JsonResponse
    {
        $userService->deleteUser($id);
        return $this->json(['success' => true]);
    }

    #[Route('/user/{id}', name: 'api_get_user', methods: ['GET'])]
    public function getUserInfo(int $id, UserService $userService): JsonResponse
    {
        $user = $userService->getUser($id);

        return $this->json([
            'id' => $user->getId(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'email' => $user->getEmail(),
            'genre' => $user->getGenre(),
        ]);
    }

    #[Route('/{id}/exists', name: 'api_user_exists', methods: ['GET'])]
    public function userExists(int $id, UserService $userService): JsonResponse
    {
        return $this->json(['exists' => $userService->userExists($id)]);
    }


    #[Route('/users', name: 'api_get_all_users', methods: ['GET'])]
    public function getAllUsers(UserService $userService): JsonResponse
    {
        $users = $userService->getAllUsers();

        $data = array_map(function (User $user) {
            return [
                'id' => $user->getId(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'email' => $user->getEmail(),
                'genre' => $user->getGenre(),
            ];
        }, $users);

        return $this->json($data);
    }
}
