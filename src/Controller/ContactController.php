<?php

namespace App\Controller;

use App\Entity\Contact;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        RateLimiterFactoryInterface $contactFormLimiter
    ): Response {

        //validation CSRF
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contact_form', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            //rate lmiter
            $limiter = $contactFormLimiter->create($request->getClientIp() ?? 'anonymous');
            $limit = $limiter->consume(1);

            if (!$limit->isAccepted()) {

                $this->addFlash(
                    'danger',
                    'Trop de demandes envoyées. Merci de réessayer dans quelques minutes.'
                );

                return $this->redirectToRoute('app_contact');
            }

            if ($request->request->get('website')) {
                return $this->redirectToRoute('app_contact');
            }
            $contactData = [
                'name' => trim((string) $request->request->get('name')),
                'phone' => trim((string) $request->request->get('phone')),
                'email' => trim((string) $request->request->get('email')),
                'client_type' => trim((string) $request->request->get('client_type')),
                'service' => trim((string) $request->request->get('service')),
                'budget' => trim((string) $request->request->get('budget')),
                'urgency' => trim((string) $request->request->get('urgency')),
                'message' => trim((string) $request->request->get('message')),
                'createdAt' => new DateTimeImmutable(),
            ];

            if (
                $contactData['name'] === '' ||
                $contactData['phone'] === '' ||
                $contactData['email'] === '' ||
                $contactData['client_type'] === '' ||
                $contactData['service'] === '' ||
                $contactData['budget'] === '' ||
                $contactData['urgency'] === '' ||
                $contactData['message'] === '' ||
                !$request->request->has('rgpd') ||
                !filter_var($contactData['email'], FILTER_VALIDATE_EMAIL) ||
                mb_strlen($contactData['name']) < 2 ||
                mb_strlen($contactData['phone']) < 10 ||
                mb_strlen($contactData['message']) < 10 ||
                mb_strlen($contactData['message']) > 3000
            ) {
                $this->addFlash('danger', 'Merci de remplir correctement tous les champs du formulaire.');

                return $this->redirectToRoute('app_contact');
            }

            $allowedClientTypes = ['Particulier', 'Professionnel', 'Association'];
            $allowedServices = [
                'Dépannage informatique',
                'Création de site web',
                'Maintenance',
            ];

            $allowedBudgets = [
                'Moins de 100 €',
                '100 € à 300 €',
                '700 € à 1200 €',
                'Plus de 1200 €',
            ];

            $allowedUrgencies = [
                'Urgent',
                'Cette semaine',
                'Ce mois-ci',
                'Pas pressé',
            ];

            if (
                !in_array($contactData['client_type'], $allowedClientTypes, true) ||
                !in_array($contactData['service'], $allowedServices, true) ||
                !in_array($contactData['budget'], $allowedBudgets, true) ||
                !in_array($contactData['urgency'], $allowedUrgencies, true)
            ) {
                $this->addFlash('danger', 'Certaines valeurs envoyées sont invalides.');

                return $this->redirectToRoute('app_contact');
            }

            $contact = new Contact();
            $contact->setFullName($contactData['name']);
            $contact->setEmail($contactData['email']);
            $contact->setPhone($contactData['phone'] ?: null);
            $contact->setClientType($contactData['client_type'] ?: null);
            $contact->setService($contactData['service'] ?: null);
            $contact->setBudget($contactData['budget'] ?: null);
            $contact->setUrgency($contactData['urgency'] ?: null);
            $contact->setSubject($contactData['service'] ?: 'Demande de contact');
            $contact->setMessage($contactData['message']);
            $contact->setStatus('new');

            $entityManager->persist($contact);
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->from('contact@rh-digitalstudio.com')
                ->replyTo($contactData['email'])
                ->to('contact@rh-digitalstudio.com')
                ->subject('Nouvelle demande - ' . ($contactData['service'] ?: 'Contact'))
                ->htmlTemplate('emails/contact.html.twig')
                ->textTemplate('emails/contact.txt.twig')
                ->context([
                    'contact' => $contactData,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Votre demande a bien été envoyée. Je vous recontacte rapidement.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig');
    }
}
