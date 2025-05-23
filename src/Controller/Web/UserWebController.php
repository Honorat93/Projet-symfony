<?php

namespace App\Controller\Web;

use App\Dto\UserDto;
use App\Form\UserType;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\Voter\UserVoter;

#[Route('/user')]
class UserWebController extends AbstractController
{
    #[Route('/create', name: 'web_user_create', methods: ['POST'])]
    public function createUser(Request $request, UserService $userService): Response
    {
        if (!$this->isGranted(UserVoter::MANAGE, null)) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à effectuer cette action.');
            return $this->redirectToRoute('web_homepage');
        }

        $dto = new UserDto();
        $form = $this->createForm(UserType::class, $dto, [
            'is_create' => true,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'validation_groups' => ['Default', 'create', 'Strict', $this->isGranted('ROLE_ADMIN') ? 'admin' : null],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->createUser($dto);
            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('web_homepage');
        }

        $users = $userService->getAllUsers();

        return $this->render('gestion_user/index.html.twig', [
            'users' => $users,
            'createForm' => $form->createView(), 
        ]);
    }

    #[Route('/form/modal', name: 'web_user_modal_form', methods: ['GET'])]
    public function modalForm(): Response
    {
        $dto = new UserDto();
        $form = $this->createForm(UserType::class, $dto, [
            'is_create' => true,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);

        return $this->render('gestion_user/_user_modal_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/update/{id}', name: 'web_update_user', methods: ['GET', 'POST'])]
    public function updateUser(int $id, Request $request, UserService $userService): Response
    {

        $user = $userService->getUser($id);

        if (!$this->isGranted(UserVoter::MANAGE, $user)) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à modifier cet utilisateur.');
            return $this->redirectToRoute('web_homepage');
        }

        $dto = $userService->createDtoFromEntity($user);
        $dto->id = $user->getId();

        $form = $this->createForm(UserType::class, $dto, [
            'is_create' => false,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'validation_groups' => ['Default', 'Strict', $this->isGranted('ROLE_ADMIN') ? 'admin' : null],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userService->updateUser($id, $dto);
            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('web_homepage');
        }

        return $this->render('gestion_user/update_user.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/delete/{id}', name: 'web_suppress_user', methods: ['POST'])]
    public function deleteUser(int $id, Request $request, UserService $userService): Response
    {
        $user = $userService->getUser($id);

        if (!$this->isGranted(UserVoter::MANAGE, $user)) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à supprimer cet utilisateur.');
            return $this->redirectToRoute('web_homepage');
        }

        if (!$this->isCsrfTokenValid('delete-user-' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('web_homepage');
        }

        $userService->deleteUser($id);
        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('web_homepage');
    }


    #[Route('/home', name: 'web_homepage', methods: ['GET'])]
    public function homepage(UserService $userService): Response
    {
        $users = $userService->getAllUsers();
        $dto = new UserDto();

        $createForm = $this->createForm(UserType::class, $dto, [
            'is_create' => true,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);

        return $this->render('gestion_user/index.html.twig', [
            'users' => $users,
            'createForm' => $createForm->createView(),
        ]);
    }

    #[Route('/profile/{id}', name: 'web_get_user', methods: ['GET'])]
    public function userProfile(int $id, UserService $userService): Response
    {
        $user = $userService->getUser($id);

        return $this->render('gestion_user/get_user.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/list', name: 'web_get_all_users', methods: ['GET'])]
    public function userList(UserService $userService): Response
    {
        $users = $userService->getAllUsers();

        return $this->render('gestion_user/get_all_users.html.twig', [
            'users' => $users,
        ]);
    }
}
