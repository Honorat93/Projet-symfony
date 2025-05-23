<?php

namespace App\Controller\Web;

use App\Form\QuoteType;
use App\Service\QuoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\Voter\QuoteVoter;

#[Route('/quotes')]
class QuoteWebController extends AbstractController
{
    #[Route('', name: 'quote_home', methods: ['GET'])]
    public function index(QuoteService $quoteService): Response
    {
        $quotes = $quoteService->getAllQuotes();

        return $this->render('gestion_devis/devis.html.twig', [
            'quotes' => $quotes,
        ]);
    }

    #[Route('/create', name: 'quote_create', methods: ['GET', 'POST'])]
    public function create(Request $request, QuoteService $quoteService): Response
    {
        $dto = $quoteService->createQuoteDto();
        $form = $this->createForm(QuoteType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $quoteService->createQuote($dto, $this->getUser());
                $this->addFlash('success', 'Le devis a été créé avec succès.');
                return $this->redirectToRoute('quote_home');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('gestion_devis/create_quote.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/update/{id}', name: 'quote_update', methods: ['GET', 'POST'])]
    public function update(int $id, Request $request, QuoteService $quoteService): Response
    {
        $quote = $quoteService->getQuote($id);
        $this->denyAccessUnlessGranted(QuoteVoter::MANAGE, $quote);

        $dto = $quoteService->createDtoFromEntity($quote);
        $form = $this->createForm(QuoteType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $quoteService->updateQuote($id, $dto);
                $this->addFlash('success', 'Le devis a été mis à jour avec succès.');
                return $this->redirectToRoute('quote_home');
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
            }
        }

        return $this->render('gestion_devis/update_quote.html.twig', [
            'form' => $form->createView(),
            'quote' => $quote,
        ]);
    }

    #[Route('/{id}', name: 'read_quote', methods: ['GET'])]
    public function read(int $id, QuoteService $quoteService): Response
    {
        $quote = $quoteService->getQuote($id);
        $dto = $quoteService->createDtoFromEntity($quote);

        $form = $this->createForm(QuoteType::class, $dto, [
            'disabled' => true,
        ]);

        return $this->render('gestion_devis/get_quote.html.twig', [
            'form' => $form->createView(),
            'quote' => $quote,
        ]);
    }

    #[Route('/download/{id}', name: 'download_quote_pdf', methods: ['GET'])]
    public function downloadPdf(int $id, QuoteService $quoteService): Response
    {
        $quote = $quoteService->getQuote($id);
        return $quoteService->generatePdf($quote);
    }

    #[Route('/delete/{id}', name: 'delete_quote', methods: ['POST'])]
    public function deleteQuote(int $id, Request $request, QuoteService $quoteService): Response
    {
        $submittedToken = $request->request->get('_token');
        $quote = $quoteService->getQuote($id);
        $this->denyAccessUnlessGranted(QuoteVoter::MANAGE, $quote);

        if (!$this->isCsrfTokenValid('delete-quote-' . $id, $submittedToken)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('quote_home');
        }

        try {
            $quoteService->deleteQuote($id);
            $this->addFlash('success', 'Le devis a été supprimé avec succès.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToRoute('quote_home');
    }
}
