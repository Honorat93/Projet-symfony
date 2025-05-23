<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\QuoteRepository;
use App\Repository\UserRepository;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class QuoteWebControllerTest extends WebTestCase
{
    private $client;
    private $quoteRepository;
    private $userRepository;
    private $csrfTokenManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->quoteRepository = $container->get(QuoteRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);
    }

    public function testQuoteCreationFlow()
    {
        // Login as admin
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        // Access create form
        $crawler = $this->client->request('GET', '/quotes/create');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créer un devis');

        // Submit valid form
        $form = $crawler->selectButton('Créer')->form([
            'quote[title]' => 'Nouveau devis',
            'quote[description]' => 'Description du devis',
            'quote[amount]' => 1500.50,
            'quote[clientFirstname]' => 'Jean',
            'quote[clientLastname]' => 'Dupont',
            'quote[clientEmail]' => 'jean.dupont@example.com',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/quotes');
        
        // Follow redirect and check flash message
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'créé avec succès');
    }

    public function testUnauthorizedUserCannotCreateQuote()
    {
        // Login as non-admin user
        $user = $this->userRepository->findOneByEmail('user@example.com');
        $this->client->loginUser($user);

        $this->client->request('GET', '/quotes/create');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testQuoteUpdateFlow()
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        $quote = $this->quoteRepository->findOneBy([]);
        $crawler = $this->client->request('GET', '/quotes/update/'.$quote->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier le devis');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'quote[title]' => 'Titre modifié',
            'quote[amount]' => 2000,
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/quotes');
        
        $updatedQuote = $this->quoteRepository->find($quote->getId());
        $this->assertEquals('Titre modifié', $updatedQuote->getTitle());
        $this->assertEquals(2000, $updatedQuote->getAmount());
    }

    public function testQuoteDeletionWithCsrfProtection()
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        $quote = $this->quoteRepository->findOneBy([]);
        $quoteId = $quote->getId();

        $token = $this->csrfTokenManager->getToken('delete-quote-'.$quoteId)->getValue();
        $this->client->request('POST', '/quotes/delete/'.$quoteId, ['_token' => $token]);
        $this->assertResponseRedirects('/quotes');
        $this->assertNull($this->quoteRepository->find($quoteId));

    
        $newQuote = $this->quoteRepository->findOneBy([]);
        $this->client->request('POST', '/quotes/delete/'.$newQuote->getId(), ['_token' => 'invalid_token']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testQuoteReadPage()
    {
        $quote = $this->quoteRepository->findOneBy([]);
        $this->client->request('GET', '/quotes/'.$quote->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $quote->getTitle());
        $this->assertSelectorExists('form:disabled');
    }

    public function testPdfGenerationContent()
    {
        $quote = $this->quoteRepository->findOneBy([]);
        $this->client->request('GET', '/quotes/download/'.$quote->getId());

        $this->assertResponseHeaderSame('Content-Type', 'application/pdf');
        
        $pdfContent = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Devis #'.$quote->getId(), $pdfContent);
        $this->assertStringContainsString($quote->getClientFullName(), $pdfContent);
        $this->assertStringContainsString(number_format($quote->getAmount(), 2, ',', ' '), $pdfContent);
    }

    public function testHomepageListing()
    {
        $this->client->request('GET', '/quotes');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
        $this->assertGreaterThan(0, $this->quoteRepository->count([]));
    }
}