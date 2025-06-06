<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Cache\Adapter\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;


class UserController extends AbstractController 
{
    private $userRepository;
    private $entityManager;
    private $serializer;
    private $passwordEncoder;
    private $jwtManager;
    private $tokenVerifier;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        UserPasswordHasherInterface $passwordEncoder,
        JWTTokenManagerInterface $jwtManager,
        TokenManagementController $tokenVerifier,
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->passwordEncoder = $passwordEncoder;
        $this->jwtManager = $jwtManager;
        $this->tokenVerifier = $tokenVerifier;
    }

   // Vérifie si un utilisateur existe en fonction de son ID
   #[Route("/user/check/{id}", name: "check_user_exists", methods: ['GET'])]
   public function checkUserExists($id)
   {
       // Recherche de l'utilisateur dans le référentiel (repository) par son ID
       $user = $this->userRepository->find($id);

       // Retourne une réponse JSON indiquant si l'utilisateur existe ou non
       return new JsonResponse(['exists' => ($user !== null)]);
   }

   // Déconnexion de l'utilisateur
   #[Route('/logout', name: 'app_logout')]
   public function logout(Request $request): Response
   {
       // Vérification du token JWT pour l'authentification
       $dataMiddleware = $this->tokenVerifier->checkToken($request);
       if (gettype($dataMiddleware) === 'boolean') {
           // En cas d'erreur de token, renvoie une réponse JSON d'erreur d'authentification
           return $this->json(
               $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
               JsonResponse::HTTP_UNAUTHORIZED
           );
       }
       $user = $dataMiddleware;

       // Supprime le cookie JWT du navigateur de l'utilisateur
       $response = new RedirectResponse($this->generateUrl('login_user'));
       $response->headers->clearCookie('jwt_token');
       
       return $response;
   }

   // Affiche le formulaire de création d'utilisateur
   #[Route('/create_user', name: 'user_create')]
   public function create(Request $request): Response
   {
       // Vérification du token JWT pour l'authentification
       $dataMiddleware = $this->tokenVerifier->checkToken($request);
       if (gettype($dataMiddleware) === 'boolean') {
           // En cas d'erreur de token, renvoie une réponse JSON d'erreur d'authentification
           return $this->json(
               $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
               JsonResponse::HTTP_UNAUTHORIZED
           );
       }
       $user = $dataMiddleware;

       // Affiche le formulaire de création d'utilisateur
       return $this->render('gestion_user/create_user.html.twig');
   }

   // Affiche le formulaire d'inscription
   #[Route('/inscription', name: 'user_register')]
   public function register(Request $request): Response
   {
       // Affiche le formulaire d'inscription
       return $this->render('gestion_user/register.html.twig');
   }

   // Affiche le formulaire de modification d'utilisateur
   #[Route('/update/{id}', name: 'modif_user')]
   public function modif(Request $request, $id): Response
   {
       try {
           // Vérification du token JWT pour l'authentification
           $dataMiddleware = $this->tokenVerifier->checkToken($request);
           if (gettype($dataMiddleware) === 'boolean') {
               // En cas d'erreur de token, renvoie une réponse JSON d'erreur d'authentification
               return $this->json(
                   $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                   JsonResponse::HTTP_UNAUTHORIZED
               );
           }
           $user = $dataMiddleware;

           // Affiche le formulaire de modification d'utilisateur
           return $this->render('gestion_user/update_user.html.twig', [
               'user' => $user,
           ]);
       } catch (\Exception $e) {
           // Gère l'exception et affiche une page d'erreur
           return $this->render('gestion_user/error.html.twig', [
               'message' => 'Une erreur est survenue : ' . $e->getMessage(),
           ]);
       }
   }

   // Affiche le formulaire de suppression d'utilisateur
   #[Route('/delete/{id}', name: 'suppress_user')]
   public function suppress(Request $request, $id): Response
   {
       try {
           // Vérification du token JWT pour l'authentification
           $dataMiddleware = $this->tokenVerifier->checkToken($request);
           if (gettype($dataMiddleware) === 'boolean') {
               // En cas d'erreur de token, renvoie une réponse JSON d'erreur d'authentification
               return $this->json(
                   $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                   JsonResponse::HTTP_UNAUTHORIZED
               );
           }
           $user = $dataMiddleware;

           // Affiche le formulaire de suppression d'utilisateur
           return $this->render('gestion_user/delete_user.html.twig', [
               'user' => $user,
           ]);
       } catch (\Exception $e) {
           // Gère l'exception et affiche une page d'erreur
           return $this->render('gestion_user/error.html.twig', [
               'message' => 'Une erreur est survenue : ' . $e->getMessage(),
           ]);
       }
   }

    #[Route('/login', name: 'login_form', methods: ['GET'])]
    public function showLoginForm(): Response
    {
        return $this->render('gestion_user/login.html.twig');
    }

    #[Route('/home', name: 'homepage')]
    public function index(Request $request): Response
    {
        
        $dataMiddleware = $this->tokenVerifier->checkToken($request);
        if (gettype($dataMiddleware) === 'boolean') {
            return $this->json(
                $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }
        $user = $dataMiddleware;
        
        $users = $this->userRepository->findAll();
        return $this->render('gestion_user/index.html.twig', [
            'users' => $users,
        ]);
    }


    #[Route('/register', name: 'create_user', methods: 'POST')]
    public function createUser(Request $request): Response
    {
        try {
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $genre = $request->request->get('genre');
            $rgpd = $request->request->get('rgpd');
            $password = $request->request->get('password');

            $emailRegex = '/^\S+@\S+\.\S+$/';
            if (!preg_match($emailRegex, $email)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Le format de l\'email est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/';
            if (!preg_match($passwordRegex, $password)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et 8 caractères minimum.",
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $existingUser = $this->userRepository->findOneBy(['email' => $email]);
            if ($existingUser) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Cet email est déjà utilisé par un autre compte.',
                ], JsonResponse::HTTP_CONFLICT);
            }


            if (empty($email) || empty($password)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'L\'email et le mot de passe sont obligatoires.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user = new User();
            $user->setFirstName($firstname)
                ->setLastName($lastname)
                ->setEmail($email)
                ->setGenre($genre)
                ->setRgpd($rgpd)
                ->setPassword($this->passwordEncoder->hashPassword($user, $password));


            if ($genre === 'M') {
                $user->setGenre('H');
            } elseif ($genre === 'F') {
                $user->setGenre('F');
            } else {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Le genre doit être spécifié M pour homme ou F pour femme.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return new RedirectResponse($this->generateUrl('login_user'));

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error: ' . $e->getMessage(),
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    #[Route('/create', name: 'create', methods: 'POST')]
    public function createU(Request $request): Response
    {
        try {


            $dataMiddleware = $this->tokenVerifier->checkToken($request);
            if (gettype($dataMiddleware) === 'boolean') {
                return $this->json(
                    $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }
            $user = $dataMiddleware;

            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $genre = $request->request->get('genre');
            $rgpd = $request->request->get('rgpd');
            $password = $request->request->get('password');

            $emailRegex = '/^\S+@\S+\.\S+$/';
            if (!preg_match($emailRegex, $email)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Le format de l\'email est invalide.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/';
            if (!preg_match($passwordRegex, $password)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et 8 caractères minimum.",
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $existingUser = $this->userRepository->findOneBy(['email' => $email]);
            if ($existingUser) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Cet email est déjà utilisé par un autre compte.',
                ], JsonResponse::HTTP_CONFLICT);
            }


            if (empty($email) || empty($password)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'L\'email et le mot de passe sont obligatoires.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user = new User();
            $user->setFirstName($firstname)
                ->setLastName($lastname)
                ->setEmail($email)
                ->setGenre($genre)
                ->setRgpd($rgpd)
                ->setPassword($this->passwordEncoder->hashPassword($user, $password));


            if ($genre === 'M') {
                $user->setGenre('H');
            } elseif ($genre === 'F') {
                $user->setGenre('F');
            } else {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Le genre doit être spécifié M pour homme ou F pour femme.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('homepage');

            // Une fois que l'utilisateur est créé avec succès
            return new JsonResponse([
                'success' => true,
                'message' => 'L\'utilisateur a été créé avec succès.',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Gestion des erreurs
            return new JsonResponse([
                'error' => 'Error: ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/login', name: 'login_user', methods: 'POST')]
    public function login(Request $request, JWTTokenManagerInterface $jwtManager): Response
    {
        try {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            if ($email === null || $password === null) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Email/password manquants.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $emailRegex = '/^\S+@\S+\.\S+$/';
            if (!preg_match($emailRegex, $email)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Le format de l'email est invalide.",
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/';
            if (!preg_match($passwordRegex, $password)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.",
                ], JsonResponse::HTTP_FORBIDDEN);
            }

            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user || !$this->passwordEncoder->isPasswordValid($user, $password)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Email/password incorrect',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $token = $jwtManager->create($user);

            // Créer un cookie pour stocker le token JWT
            $cookie = Cookie::create('jwt_token', $token);
    
            // Créer une réponse de redirection vers la page d'accueil
            $response = new RedirectResponse($this->generateUrl('homepage'));
    
            // Ajouter le cookie à la réponse
            $response->headers->setCookie($cookie);
    
            return $response;
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error: ' . $e->getMessage(),
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
    


    #[Route('/user/update/{id}', name: 'update_user', methods: ['POST'])]
    public function updateUser(Request $request, int $id): Response
    {
        try {
            // Vérifie le token de l'utilisateur
            $dataMiddleware = $this->tokenVerifier->checkToken($request);
            if (gettype($dataMiddleware) === 'boolean') {
                return $this->json(
                    $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }
            $user = $dataMiddleware;

            // Vérifie si l'ID de l'utilisateur est présent dans la requête
            if (!$id) {
                return $this->json([
                    'error' => true,
                    'message' => "L'ID de l'utilisateur est obligatoire pour cette requête."
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Récupère l'utilisateur à mettre à jour
            $user = $this->userRepository->find($id);

            // Vérifie si l'utilisateur existe
            if (!$user) {
                return $this->json([
                    'error' => true,
                    'message' => "Aucun utilisateur trouvé correspondant à l'ID fourni."
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            // Récupère les données de la requête
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $genre = $request->request->get('genre');
            $rgpd = $request->request->get('rgpd');

            // Vérifie si l'e-mail est déjà utilisé par un autre utilisateur
            $existingUser = $this->userRepository->findOneBy(['email' => $email]);

            if ($existingUser && $existingUser->getId() !== $id) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Cet email est déjà utilisé par un autre compte.',
                ], JsonResponse::HTTP_CONFLICT);
            }

            // Met à jour les champs de l'utilisateur si les données sont fournies
            if ($firstname !== null) {
                $user->setFirstName($firstname);
            }

            if ($lastname !== null) {
                $user->setLastName($lastname);
            }

            if ($email !== null) {
                $user->setEmail($email);
            }

            if ($genre !== null) {
                $user->setGenre($genre);
            }

            if ($rgpd !== null) {
                $user->setRgpd($rgpd);
            }

            // Enregistre les modifications dans la base de données
            $this->entityManager->flush();

            // Redirige vers la page d'accueil après la mise à jour
            return $this->redirectToRoute('homepage');

            // Retourne une réponse JSON indiquant le succès de la mise à jour
            return $this->json([
                'error' => false,
                'message' => "Utilisateur mis à jour avec succès."
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            // Gère les erreurs
            return $this->json(['error' => true, 'message' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/user/{id}', name: 'delete_user', methods: ['POST'])]
    public function deleteUser(Request $request, $id): JsonResponse
    {
        try {
            $dataMiddleware = $this->tokenVerifier->checkToken($request);
            if (gettype($dataMiddleware) === 'boolean') {
                return $this->json(
                    $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }
    
            $user = $this->userRepository->find($id);
    
            if (!$user) {
                return new JsonResponse([
                    'error' => true,
                    'message' => 'Utilisateur non trouvé.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }
    
            // Supprimer les devis associés à l'utilisateur
            foreach ($user->getQuotes() as $quote) {
                $this->entityManager->remove($quote);
            }
    
            $this->entityManager->remove($user);
            $this->entityManager->flush();
    
            return new JsonResponse([
                'error' => false,
                'message' => 'L\'utilisateur a été supprimé avec succès, ainsi que ses devis associés.',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Une erreur est survenue lors de la suppression de l\'utilisateur : ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
 #[Route('/user/{id}', name: 'get_user', methods: ['GET'])]
    public function getUserInfo(Request $request, int $id): Response
    {
        try {
            // Vérifiez le jeton d'authentification
            $dataMiddleware = $this->tokenVerifier->checkToken($request);
            if (gettype($dataMiddleware) === 'boolean') {
                return $this->json(
                    $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }
            // Utilisateur authentifié
            $user = $dataMiddleware;

            // Récupérer l'utilisateur par son ID
            $user = $this->userRepository->find($id);

            if (!$user) {
                throw new \Exception('Utilisateur non trouvé.');
            }

            // Rendu de la vue Twig avec les données de l'utilisateur
            return $this->render('gestion_user/get_user.html.twig', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            // Gestion des erreurs
            return $this->render('gestion_user/error.html.twig', [
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ]);
        }
    }

    #[Route('/users', name: 'get_all_users', methods: ['GET'])]
    public function getAllUsers(Request $request): Response
    {
        try {
            // Vérifiez le jeton d'authentification
            $dataMiddleware = $this->tokenVerifier->checkToken($request);
            if (gettype($dataMiddleware) === 'boolean') {
                return $this->json(
                    $this->tokenVerifier->sendJsonErrorToken($dataMiddleware),
                    JsonResponse::HTTP_UNAUTHORIZED
                );
            }
            // Utilisateur authentifié
            $user = $dataMiddleware;

            // Récupérer tous les utilisateurs
            $users = $this->userRepository->findAll();

            // Rendu de la vue Twig avec la liste des utilisateurs
            return $this->render('gestion_user/get_all_users.html.twig', [
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            // Gestion des erreurs
            return new JsonResponse([
                'error' => true,
                'message' => 'Une erreur est survenue lors de la récupération des utilisateurs : ' . $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}