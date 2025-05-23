<?php

namespace App\Security\Voter;

use App\Entity\Quote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class QuoteVoter extends Voter
{
    public const MANAGE = 'QUOTE_MANAGE';
    public const VIEW = 'QUOTE_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::MANAGE, self::VIEW]) && $subject instanceof Quote;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Quote $quote */
        $quote = $subject;

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isOwner = $quote->getCreatorEmail() === $user->getUserIdentifier();

        return match ($attribute) {
            self::MANAGE => $isAdmin || $isOwner,
            self::VIEW => true,
            default => false,
        };
    }
}
