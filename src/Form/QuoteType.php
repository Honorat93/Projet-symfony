<?php

namespace App\Form;

use App\Dto\QuoteDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant (€)',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Type([
                        'type' => 'numeric',
                        'message' => 'Le montant doit être un nombre valide.'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\d+(\.\d{1,2})?$/',
                        'message' => 'Le montant doit être un nombre valide sans lettres.',
                    ]),
                ],
            ])
            ->add('clientFirstname', TextType::class, [
                'label' => 'Prénom du client',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('clientLastname', TextType::class, [
                'label' => 'Nom du client',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('clientEmail', EmailType::class, [
                'label' => 'Email du client',
                'constraints' => [new Assert\Email()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteDto::class,
        ]);
    }
}
