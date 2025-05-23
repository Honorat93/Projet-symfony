<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueQuoteTitle extends Constraint
{
    public string $message = 'Un devis avec le titre "{{ value }}" existe déjà.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
