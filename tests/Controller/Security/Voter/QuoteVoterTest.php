<?php

namespace App\Tests\Security\Voter;

use App\Entity\Quote;
use App\Security\Voter\QuoteVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class QuoteVoterTest extends TestCase
{
    private QuoteVoter $voter;
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->voter = new QuoteVoter();
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testVoteDeniedIfNoUser(): void
    {
        $this->token->method('getUser')->willReturn(null);

        $quote = new Quote();
        $result = $this->voter->vote($this->token, $quote, ['QUOTE_MANAGE']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanManageQuote(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('admin@example.com');
        $user->method('getRoles')->willReturn(['ROLE_ADMIN']);

        $this->token->method('getUser')->willReturn($user);

        $quote = new Quote();
        $quote->setCreatorEmail('user@example.com');

        $result = $this->voter->vote($this->token, $quote, ['QUOTE_MANAGE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanManageOwnQuote(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('owner@example.com');
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $this->token->method('getUser')->willReturn($user);

        $quote = new Quote();
        $quote->setCreatorEmail('owner@example.com');

        $result = $this->voter->vote($this->token, $quote, ['QUOTE_MANAGE']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotManageOthersQuote(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user@example.com');
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $this->token->method('getUser')->willReturn($user);

        $quote = new Quote();
        $quote->setCreatorEmail('other@example.com');

        $result = $this->voter->vote($this->token, $quote, ['QUOTE_MANAGE']);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnyoneCanViewQuote(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);
        $this->token->method('getUser')->willReturn($user);

        $quote = new Quote();
        $result = $this->voter->vote($this->token, $quote, ['QUOTE_VIEW']);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}
