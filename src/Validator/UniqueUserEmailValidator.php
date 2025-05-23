<?php

namespace App\Validator;

use App\Dto\UserDto;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUserEmailValidator extends ConstraintValidator
{
    public function __construct(private UserRepository $userRepository) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserEmail) {
            throw new UnexpectedTypeException($constraint, UniqueUserEmail::class);
        }

        if (!$value instanceof UserDto) {
            return; 
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $value->email]);

        if ($existingUser && ($value->id === null || $existingUser->getId() !== $value->id)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('email')
                ->setParameter('{{ value }}', $value->email)
                ->addViolation();
        }
    }
}
