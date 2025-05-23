<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class UserWebControllerTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $csrfTokenManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->userRepository = $container->get(UserRepository::class);
        $this->csrfTokenManager = $container->get(CsrfTokenManagerInterface::class);
    }

    public function testAccessDeniedOnUserCreation()
    {
        $this->client->request('POST', '/user/create');
        $this->assertResponseRedirects('/user/home');

        // Suivre la redirection pour vérifier le message flash
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
        $this->assertSelectorTextContains('.alert-danger', 'autorisé');
    }

    public function testCreateUserAsAdmin()
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/user/home');
        $form = $crawler->selectButton('Créer')->form([
            'user[firstname]' => 'Test',
            'user[lastname]' => 'User',
            'user[email]' => 'test@example.com',
            'user[encrypte]' => 'password123',
            'user[genre]' => 'M',
            'user[rgpd]' => true,
        ]);

        $this->client->submit($form);

        // On suit la redirection pour valider le message
        $this->assertResponseRedirects('/user/home');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'créé avec succès');
    }

    public function testUpdateUserFormIsAccessible()
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        $user = $this->userRepository->findOneByEmail('test@example.com');
        $this->client->request('GET', '/user/update/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="user[firstname]"]');
    }

    public function testDeleteUserAsAdmin()
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        $user = $this->userRepository->findOneByEmail('test@example.com');
        $userId = $user->getId();

        $token = $this->csrfTokenManager->getToken('delete-user-' . $userId)->getValue();

        $this->client->request('POST', '/user/delete/' . $userId, [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/user/home');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'supprimé avec succès');

        $this->assertNull($this->userRepository->find($userId));
    }

    public function testLoginFormIsAccessible()
    {
        $this->client->request('GET', '/user/login');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testUserListPageAccessible()
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        $this->client->request('GET', '/user/list');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
        $this->assertSelectorExists('tr');
    }

    public function testUserProfileAccessible()
    {
        $admin = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->loginUser($admin);

        $user = $this->userRepository->findOneByEmail('admin@example.com');
        $this->client->request('GET', '/user/profile/' . $user->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $user->getEmail());
    }
}
