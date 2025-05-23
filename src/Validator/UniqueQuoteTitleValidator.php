<?php

namespace App\Validator;

use App\Dto\QuoteDto;
use App\Repository\QuoteRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueQuoteTitleValidator extends ConstraintValidator
{
    public function __construct(private QuoteRepository $quoteRepository) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueQuoteTitle) {
            throw new UnexpectedTypeException($constraint, UniqueQuoteTitle::class);
        }

        if (!$value instanceof QuoteDto) {
            return;
        }

        $existing = $this->quoteRepository->findOneBy(['title' => $value->title]);

        if ($existing && ($value->id === null || $existing->getId() !== $value->id)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('title')
                ->setParameter('{{ value }}', $value->title)
                ->addViolation();
        }
    }
}
