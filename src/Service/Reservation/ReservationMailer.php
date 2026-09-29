<?php

namespace App\Service\Reservation;

use App\Entity\Reservation;
use App\Enum\ReservationStatus;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final readonly class ReservationMailer
{
    private const SENDER_NAME = 'Penzion Tesák-Čerňava';

    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(env: 'CONTACT_RECIPIENT_EMAIL')]
        private string $ownerEmail,
        #[Autowire(env: 'MAILER_FROM')]
        private string $senderEmail,
    ) {}

    /** New inquiry: heads-up to the owner, receipt to the guest. */
    public function sendInquiry(Reservation $reservation): void
    {
        $this->mailer->send($this->email()
            ->to($this->ownerEmail)
            ->replyTo(new Address((string) $reservation->getEmail(), (string) $reservation->getGuestName()))
            ->subject(sprintf('Nová rezervace ke schválení – %s, %s', $reservation->getGuestName(), $reservation->getArrival()->format('j. n.')))
            ->htmlTemplate('email/reservation/owner_inquiry.html.twig')
            ->context(['reservation' => $reservation]));

        $this->toGuest($reservation, 'Přijali jsme Vaši poptávku – Penzion Tesák-Čerňava', 'email/reservation/guest_received.html.twig');
    }

    /**
     * Tells the guest the owner's decision after a status change.
     *
     * @return bool whether an e-mail went out (only confirm / reject are announced, and only to guests with an e-mail)
     */
    public function sendStatusChange(Reservation $reservation, ReservationStatus $previousStatus): bool
    {
        $status = $reservation->getStatus();
        if ($status === $previousStatus) {
            return false;
        }

        return match ($status) {
            ReservationStatus::Confirmed => $this->toGuest($reservation, 'Rezervace potvrzena – Penzion Tesák-Čerňava', 'email/reservation/guest_confirmed.html.twig'),
            ReservationStatus::Rejected => $this->toGuest($reservation, 'K Vaší poptávce – Penzion Tesák-Čerňava', 'email/reservation/guest_rejected.html.twig'),
            default => false,
        };
    }

    private function toGuest(Reservation $reservation, string $subject, string $template): bool
    {
        if (null === $reservation->getEmail()) {
            return false;
        }

        $this->mailer->send($this->email()
            ->to(new Address($reservation->getEmail(), (string) $reservation->getGuestName()))
            ->subject($subject)
            ->htmlTemplate($template)
            ->context(['reservation' => $reservation]));

        return true;
    }

    private function email(): TemplatedEmail
    {
        return (new TemplatedEmail())->from(new Address($this->senderEmail, self::SENDER_NAME));
    }
}
