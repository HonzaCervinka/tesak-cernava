<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactFormData
{
    public function __construct(
        #[Assert\NotBlank]
        public string  $name,
        #[Assert\NotBlank]
        #[Assert\Email]
        public string  $email,
        #[Assert\NotBlank]
        public string  $phone,
        #[Assert\NotBlank]
        public string  $stayType,
        #[Assert\NotBlank]
        public string  $dateFrom,
        #[Assert\NotBlank]
        public string  $dateTo,
        public ?int    $adults,
        public ?int    $children,
        public ?string $message,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            phone: (string) ($data['phone'] ?? ''),
            stayType: (string) ($data['stayType'] ?? ''),
            dateFrom: (string) ($data['dateFrom'] ?? ''),
            dateTo: (string) ($data['dateTo'] ?? ''),
            adults: isset($data['adults']) && $data['adults'] !== '' ? (int) $data['adults'] : null,
            children: isset($data['children']) && $data['children'] !== '' ? (int) $data['children'] : null,
            message: $data['message'] ?: null,
        );
    }
}
