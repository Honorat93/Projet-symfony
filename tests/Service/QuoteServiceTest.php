<?php

namespace App\Tests\Service;

use App\Dto\QuoteDto;
use App\Entity\Quote;
use App\Repository\QuoteRepository;
use App\Service\QuoteService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuoteServiceTest extends TestCase
{
    private $em;
    private $quoteRepository;
    private $validator;
    private $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->quoteRepository = $this->createMock(QuoteRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->service = new QuoteService(
            $this->em,
            $this->quoteRepository,
            $this->validator
        );
    }

    public function testCreateQuote()
    {
        $dto = new QuoteDto();
        $dto->title = 'Test';
        $dto->description = 'Test description';
        $dto->amount = 100.0;
        $dto->clientFirstname = 'Jean';
        $dto->clientLastname = 'Dupont';
        $dto->clientEmail = 'jean@example.com';

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('admin@example.com');

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $quote = $this->service->createQuote($dto, $user);

        $this->assertInstanceOf(Quote::class, $quote);
        $this->assertEquals('Test', $quote->getTitle());
    }

    public function testUpdateQuoteNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $dto = new QuoteDto();
        $dto->title = 'Update';

        $this->quoteRepository->method('find')->willReturn(null);

        $this->service->updateQuote(999, $dto);
    }

    public function testUpdateQuote()
    {
        $quote = new Quote();
        $quote->setTitle('Old');

        $dto = new QuoteDto();
        $dto->title = 'New';
        $dto->description = 'Desc';
        $dto->amount = 250;
        $dto->clientFirstname = 'Jean';
        $dto->clientLastname = 'Dupont';
        $dto->clientEmail = 'jean@example.com';

        $this->quoteRepository->method('find')->willReturn($quote);
        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->updateQuote(1, $dto);
        $this->assertEquals('New', $updated->getTitle());
    }

    public function testDeleteQuote()
    {
        $quote = new Quote();
        $this->quoteRepository->method('find')->willReturn($quote);

        $this->em->expects($this->once())->method('remove')->with($quote);
        $this->em->expects($this->once())->method('flush');

        $this->service->deleteQuote(1);
    }

    public function testDeleteQuoteNotFound()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->quoteRepository->method('find')->willReturn(null);

        $this->service->deleteQuote(999);
    }

    public function testGetQuote()
    {
        $quote = new Quote();
        $this->quoteRepository->method('find')->willReturn($quote);

        $result = $this->service->getQuote(1);
        $this->assertInstanceOf(Quote::class, $result);
    }

    public function testGetQuoteNotFound()
    {
        $this->expectException(NotFoundHttpException::class);
        $this->quoteRepository->method('find')->willReturn(null);

        $this->service->getQuote(999);
    }

    public function testGeneratePdf()
    {
        $quote = new Quote();


        $reflection = new \ReflectionClass($quote);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($quote, 1);

        $quote->setTitle('Titre test')
            ->setDescription('Une description')
            ->setAmount(1200.50)
            ->setClientFirstname('Jean')
            ->setClientLastname('Dupont')
            ->setClientEmail('jean@example.com');

        $response = $this->service->generatePdf($quote);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('%PDF', $response->getContent());
    }


    public function testHandleUpdateFromRequest()
    {
        $quote = new Quote();

        $request = new Request([], [
            'title' => 'Titre modifié',
            'description' => 'Desc',
            'amount' => 123.45,
            'client_firstname' => 'Jean',
            'client_lastname' => 'Dupont',
            'client_email' => 'jean@example.com',
        ]);

        $this->quoteRepository->method('find')->willReturn($quote);

        $result = $this->service->handleUpdateFromRequest(1, $request);
        $this->assertEquals('Titre modifié', $result->getTitle());
    }
}
