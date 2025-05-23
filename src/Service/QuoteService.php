<?php

namespace App\Service;

use App\Dto\QuoteDto;
use App\Entity\Quote;
use App\Repository\QuoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Dompdf\Dompdf;
use Dompdf\Options;

class QuoteService
{
    public function __construct(
        private EntityManagerInterface $em,
        private QuoteRepository $quoteRepository,
    ) {}

    public function createQuoteDto(): QuoteDto
    {
        return new QuoteDto();
    }

    public function createDtoFromEntity(Quote $quote): QuoteDto
    {
        $dto = new QuoteDto();
        $dto->id = $quote->getId();
        $dto->title = $quote->getTitle();
        $dto->description = $quote->getDescription();
        $dto->amount = $quote->getAmount();
        $dto->clientFirstname = $quote->getClientFirstname();
        $dto->clientLastname = $quote->getClientLastname();
        $dto->clientEmail = $quote->getClientEmail();

        return $dto;
    }

    public function createQuote(QuoteDto $dto, UserInterface $user): Quote
    {
        $quote = new Quote();
        $quote->setCreatedAt(new \DateTimeImmutable());
        $quote->setCreatorEmail($user->getUserIdentifier());

        $this->hydrateQuoteFromDto($quote, $dto);

        $this->em->persist($quote);
        $this->em->flush();

        return $quote;
    }

    public function updateQuote(int $id, QuoteDto $dto): Quote
    {
        $quote = $this->getQuote($id);
        $this->hydrateQuoteFromDto($quote, $dto);

        $this->em->flush();
        return $quote;
    }

    public function deleteQuote(int $id): void
    {
        $quote = $this->getQuote($id);
        $this->em->remove($quote);
        $this->em->flush();
    }

    public function getQuote(int $id): Quote
    {
        $quote = $this->quoteRepository->find($id);
        if (!$quote) {
            throw new NotFoundHttpException("Devis non trouvé.");
        }

        return $quote;
    }

    public function getAllQuotes(): array
    {
        return $this->quoteRepository->findAll();
    }

    public function getQuotesForUser(string $email): array
    {
        return $this->quoteRepository->findBy(['creatorEmail' => $email]);
    }

    public function generatePdf(Quote $quote): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->setIsRemoteEnabled(true);

        $dompdf = new Dompdf($options);
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Devis #{$quote->getId()}</title>
            <style>
                body {
                    font-family: Helvetica, sans-serif;
                    padding: 30px;
                    color: #333;
                }
                h1 {
                    text-align: center;
                    margin-bottom: 30px;
                    color: #007bff;
                }
                .section {
                    margin-bottom: 20px;
                }
                .section p {
                    margin: 5px 0;
                    font-size: 14px;
                }
                .section strong {
                    width: 150px;
                    display: inline-block;
                }
                .amount {
                    font-size: 16px;
                    font-weight: bold;
                    margin-top: 15px;
                    color: #000;
                }
            </style>
        </head>
        <body>
            <h1>Devis #{$quote->getId()}</h1>
            <div class="section">
                <p><strong>Client :</strong> {$quote->getClientFirstname()} {$quote->getClientLastname()}</p>
                <p><strong>Email :</strong> {$quote->getClientEmail()}</p>
            </div>
            <div class="section">
                <p><strong>Titre :</strong> {$quote->getTitle()}</p>
                <p><strong>Description :</strong> {$quote->getDescription()}</p>
                <p class="amount"><strong>Montant :</strong> {$quote->getAmount()} €</p>
            </div>
        </body>
        </html>
        HTML;

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="devis_'.$quote->getId().'.pdf"',
        ]);
    }

    private function hydrateQuoteFromDto(Quote $quote, QuoteDto $dto): void
    {
        $quote->setTitle($dto->title)
              ->setDescription($dto->description)
              ->setAmount($dto->amount)
              ->setClientFirstname($dto->clientFirstname)
              ->setClientLastname($dto->clientLastname)
              ->setClientEmail($dto->clientEmail);
    }
}
