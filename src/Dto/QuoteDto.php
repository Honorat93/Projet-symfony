<?php 

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\UniqueQuoteTitle;

#[UniqueQuoteTitle]
class QuoteDto
{
    public ?int $id = null;

    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 3, max: 255)]
    public string $title;

    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10)]
    public string $description;

    #[Assert\NotNull(message: "Le montant est obligatoire.")]
    #[Assert\Positive(message: "Le montant doit être positif.")]
    public float $amount;

    #[Assert\NotBlank(message: "Le prénom du client est obligatoire.")]
    public string $clientFirstname;

    #[Assert\NotBlank(message: "Le nom du client est obligatoire.")]
    public string $clientLastname;

    #[Assert\NotBlank(message: "L'email du client est obligatoire.")]
    #[Assert\Email(message: "L'email du client n'est pas valide.")]
    public string $clientEmail;

}