<?php

namespace App\Service;

use App\Entity\Intervention;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class DocumentMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private PdfGenerator $pdfGenerator,
        private Environment $twig
    ) {
    }

    public function sendQuote(Intervention $intervention): void
    {
        $pdf = $this->pdfGenerator->generate('admin/pdf/quote.html.twig', [
            'intervention' => $intervention,
        ]);

        $html = $this->twig->render('emails/quote_email.html.twig', [
        'intervention' => $intervention,
    ]);

        $email = (new Email())
            ->from('contact@rh-digitalstudio.com')
            ->to($intervention->getClient()->getEmail())
            ->subject('Votre devis RH Digital Studio - '.$intervention->getQuoteNumber())
            ->html($html)
            ->text(
                "Bonjour,\n\nVeuillez trouver ci-joint votre devis.\n\nCordialement,\nRH Digital Studio"
            )
            ->attach($pdf, $intervention->getQuoteNumber().'.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    public function sendInvoice(Intervention $intervention): void
    {
        $pdf = $this->pdfGenerator->generate('admin/pdf/invoice.html.twig', [
            'intervention' => $intervention,
        ]);
        $html = $this->twig->render('emails/invoice_email.html.twig', [
            'intervention' => $intervention,
        ]);

        $email = (new Email())
            ->from('contact@rh-digitalstudio.com')
            ->to($intervention->getClient()->getEmail())
            ->subject('Votre facture RH Digital Studio - '.$intervention->getInvoiceNumber())
            ->html($html)
            ->text(
                "Bonjour,\n\nVeuillez trouver ci-joint votre facture.\n\nCordialement,\nRH Digital Studio"
            )
            ->attach($pdf, $intervention->getInvoiceNumber().'.pdf', 'application/pdf');

        $this->mailer->send($email);
    }

    public function sendReminder(Intervention $intervention): void
    {
        $html = $this->twig->render('emails/reminder_email.html.twig', [
            'intervention' => $intervention,
        ]);

        $email = (new Email())
            ->from('contact@rh-digitalstudio.com')
            ->to($intervention->getClient()->getEmail())
            ->subject('Relance facture - '.$intervention->getInvoiceNumber())
            ->html($html)
            ->text(
                "Bonjour,\n\nSauf erreur de notre part, votre facture est toujours en attente de règlement.\n\nCordialement,\nRH Digital Studio"
            );

        $this->mailer->send($email);
    }
}
