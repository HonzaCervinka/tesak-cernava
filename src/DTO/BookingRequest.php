<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** Reservation inquiry submitted from the public availability timeline. */
final readonly class BookingRequest
{
    public const MAX_NIGHTS = 30;

    public function __construct(
        #[Assert\Positive]
        public int $roomId,
        #[Assert\NotNull(message: 'Vyberte datum příjezdu.')]
        #[Assert\GreaterThanOrEqual('today', message: 'Příjezd nemůže být v minulosti.')]
        public ?\DateTimeImmutable $arrival,
        #[Assert\NotNull(message: 'Vyberte datum odjezdu.')]
        public ?\DateTimeImmutable $departure,
        #[Assert\NotBlank(message: 'Vyplňte jméno.')]
        #[Assert\Length(max: 180)]
        public string $name,
        #[Assert\NotBlank(message: 'Vyplňte e-mail.')]
        #[Assert\Email(message: 'Zadejte platný e-mail.')]
        #[Assert\Length(max: 180)]
        public string $email,
        #[Assert\NotBlank(message: 'Vyplňte telefon.')]
        #[Assert\Length(max: 40)]
        public string $phone,
        #[Assert\NotNull(message: 'Zadejte počet osob.')]
        #[Assert\Positive(message: 'Zadejte počet osob.')]
        public ?int $guests,
        #[Assert\Length(max: 1000)]
        public ?string $note,
        #[Assert\IsTrue(message: 'Pro odeslání je potřeba souhlas se zpracováním údajů.')]
        public bool $consent,
        /** Honeypot – hidden from people, bots fill it. */
        public string $website = '',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            roomId: (int) ($data['roomId'] ?? 0),
            arrival: self::date($data['arrival'] ?? null),
            departure: self::date($data['departure'] ?? null),
            name: trim((string) ($data['name'] ?? '')),
            email: trim((string) ($data['email'] ?? '')),
            phone: trim((string) ($data['phone'] ?? '')),
            guests: isset($data['guests']) && '' !== $data['guests'] ? (int) $data['guests'] : null,
            note: isset($data['note']) && '' !== trim((string) $data['note']) ? trim((string) $data['note']) : null,
            consent: true === ($data['consent'] ?? false),
            website: (string) ($data['website'] ?? ''),
        );
    }

    #[Assert\Callback]
    public function validateStay(ExecutionContextInterface $context): void
    {
        if (null === $this->arrival || null === $this->departure) {
            return;
        }
        if ($this->departure <= $this->arrival) {
            $context->buildViolation('Odjezd musí být po příjezdu.')->atPath('departure')->addViolation();
        } elseif ($this->arrival->diff($this->departure)->days > self::MAX_NIGHTS) {
            $context->buildViolation(sprintf('Online lze poptat nejvýše %d nocí, delší pobyt s námi domluvte.', self::MAX_NIGHTS))
                ->atPath('departure')->addViolation();
        }
    }

    public function isSpam(): bool
    {
        return '' !== $this->website;
    }

    private static function date(mixed $value): ?\DateTimeImmutable
    {
        if (!\is_string($value)) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value ? $date : null;
    }
}
