<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactFormData
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['web', 'eshop', 'programovani', 'konzultace'])]
        public string  $type,
        public ?string $budget,
        public ?string $deadline,
        public ?string $url,
        public bool    $hasLogo,
        public bool    $hasTexts,
        #[Assert\NotBlank]
        public string  $name,
        public ?string $company,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string  $email,
        public ?string $phone,
        public ?string $note,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? ''),
            budget: $data['budget'] ?: null,
            deadline: $data['deadline'] ?: null,
            url: $data['url'] ?: null,
            hasLogo: (bool) ($data['hasLogo'] ?? false),
            hasTexts: (bool) ($data['hasTexts'] ?? false),
            name: (string) ($data['name'] ?? ''),
            company: $data['company'] ?: null,
            email: (string) ($data['email'] ?? ''),
            phone: $data['phone'] ?: null,
            note: $data['note'] ?: null,
        );
    }
}
