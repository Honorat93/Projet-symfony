<?php

namespace App\Controller\Api;

use App\Entity\Quote;
use App\Repository\QuoteRepository;
use App\Repository\UserRepository;
use App\Service\TokenVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TCPDF;

#[Route('/api/quotes')]
class QuoteApiController extends AbstractController
{
    public function __construct(
        private QuoteRepository $quoteRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private TokenVerifier $tokenVerifier
    ) {}

    #[Route('', name: 'api_create_quote', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->tokenVerifier->ensureTokenIsValid($request);

        $data = $request->request->all();
        if (empty($data['user_email']) || empty($data['title']) || empty($data['description']) || empty($data['amount'])) {
            return $this->json(['error' => true, 'message' => 'Champs obligatoires manquants.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $userEntity = $this->userRepository->findOneByEmail($data['user_email']);
        if (!$userEntity) {
            return $this->json(['error' => true, 'message' => "Utilisateur introuvable."], JsonResponse::HTTP_NOT_FOUND);
        }

        $quote = (new Quote())
            ->setTitle($data['title'])
            ->setDescription($data['description'])
            ->setAmount($data['amount'])
            ->setCreatedAt(new \DateTime())
            ->setUser($userEntity);

        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'quote_id' => $quote->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_read_quote', methods: ['GET'])]
    public function read(Request $request, int $id): JsonResponse
    {
        $this->tokenVerifier->ensureTokenIsValid($request);
        $quote = $this->quoteRepository->find($id);

        if (!$quote) {
            return $this->json(['error' => true, 'message' => 'Devis non trouvé.'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $quote->getId(),
            'title' => $quote->getTitle(),
            'description' => $quote->getDescription(),
            'amount' => $quote->getAmount(),
            'created_at' => $quote->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/{id}', name: 'api_update_quote', methods: ['POST'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $this->tokenVerifier->ensureTokenIsValid($request);
        $quote = $this->quoteRepository->find($id);
        if (!$quote) {
            return $this->json(['error' => true, 'message' => 'Devis non trouvé.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $request->request->all();
        if (empty($data['title']) || empty($data['description']) || empty($data['amount'])) {
            return $this->json(['error' => true, 'message' => 'Champs manquants.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $quote->setTitle($data['title'])
              ->setDescription($data['description'])
              ->setAmount($data['amount']);

        $this->entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Devis mis à jour.']);
    }

    #[Route('/{id}', name: 'api_delete_quote', methods: ['DELETE'])]
    public function delete(Request $request, int $id): JsonResponse
    {
        $this->tokenVerifier->ensureTokenIsValid($request);
        $quote = $this->quoteRepository->find($id);
        if (!$quote) {
            return $this->json(['error' => true, 'message' => 'Devis non trouvé.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($quote);
        $this->entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Devis supprimé.']);
    }

    #[Route('/{id}/download', name: 'api_download_quote_pdf', methods: ['GET'])]
    public function downloadPdf(Request $request, int $id): Response
    {
        $this->tokenVerifier->ensureTokenIsValid($request);
        $quote = $this->quoteRepository->find($id);
        if (!$quote) {
            return $this->json(['error' => true, 'message' => 'Devis non trouvé.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $user = $quote->getUser();

        $pdf = new TCPDF();
        $pdf->AddPage();
        $html = "<h1>{$quote->getTitle()}</h1>
                 <p><strong>Description:</strong> {$quote->getDescription()}</p>
                 <p><strong>Montant:</strong> {$quote->getAmount()}</p>
                 <h2>Client:</h2>
                 <p>{$user->getFirstName()} {$user->getLastName()} ({$user->getEmail()})</p>";
        $pdf->writeHTML($html);

        return new Response($pdf->Output("quote_{$quote->getId()}.pdf", 'D'), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="quote.pdf"',
        ]);
    }

    #[Route('', name: 'api_get_all_quotes', methods: ['GET'])]
    public function getAll(Request $request): JsonResponse
    {
        $this->tokenVerifier->ensureTokenIsValid($request);
        $quotes = $this->quoteRepository->findAll();
        $data = array_map(function (Quote $q) {
            return [
                'id' => $q->getId(),
                'title' => $q->getTitle(),
                'description' => $q->getDescription(),
                'amount' => $q->getAmount(),
                'created_at' => $q->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $quotes);

        return $this->json($data);
    }
}
