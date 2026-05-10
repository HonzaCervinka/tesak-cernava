<?php

namespace App\Controller;

use App\DTO\ContactFormData;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ValidatorInterface $validator,
        #[Autowire(env: 'CONTACT_RECIPIENT_EMAIL')]
        private readonly string $recipientEmail,
        #[Autowire(env: 'MAILER_FROM')]
        private readonly string $senderEmail,
    ) {}

    #[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $formData = ContactFormData::fromArray($data);

        $violations = $this->validator->validate($formData);
        if (count($violations) > 0) {
            return $this->json(['error' => 'Vyplň povinná pole.'], 422);
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'Web Jan Červinka'))
            ->to($this->recipientEmail)
            ->replyTo(new Address($formData->email, $formData->name))
            ->subject('Nová poptávka — ' . $formData->name)
            ->htmlTemplate('email/contact.html.twig')
            ->context(['data' => $formData]);

        $this->mailer->send($email);

        $confirmation = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'Jan Červinka'))
            ->to(new Address($formData->email, $formData->name))
            ->subject('Dostal jsem tvoji poptávku — ozvu se do 24 h')
            ->htmlTemplate('email/contact_confirmation.html.twig')
            ->context(['data' => $formData]);

        $this->mailer->send($confirmation);

        return $this->json(['message' => 'OK']);
    }
}
