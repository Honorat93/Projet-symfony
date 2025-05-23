<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueUserEmail extends Constraint
{
    public string $message = 'Un utilisateur avec l\'email "{{ value }}" existe déjà.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
