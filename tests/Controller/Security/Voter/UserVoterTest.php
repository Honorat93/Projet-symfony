<?php

namespace App\Tests\Security\Voter;

use App\Entity\User;
use App\Security\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserVoterTest extends TestCase
{
    private UserVoter $voter;
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->voter = new UserVoter();
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testAdminCanManage(): void
    {
        $admin = $this->createMock(UserInterface::class);
        $admin->method('getRoles')->willReturn(['ROLE_ADMIN']);

        $this->token->method('getUser')->willReturn($admin);

        $targetUser = new User();
        $result = $this->voter->vote($this->token, $targetUser, [UserVoter::MANAGE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonAdminCannotManage(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        $this->token->method('getUser')->willReturn($user);

        $targetUser = new User();
        $result = $this->voter->vote($this->token, $targetUser, [UserVoter::MANAGE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAnonymousCannotManage(): void
    {
        $this->token->method('getUser')->willReturn(null);

        $targetUser = new User();
        $result = $this->voter->vote($this->token, $targetUser, [UserVoter::MANAGE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
