<?php

namespace App\Controller\Admin;

use App\Entity\Intervention;
use App\Service\DocumentNumberGenerator;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PdfController extends AbstractController
{
    #[Route('/admin/intervention/{id}/facture-pdf', name: 'admin_intervention_invoice_pdf')]
    public function invoice(
        Intervention $intervention,
        DocumentNumberGenerator $numberGenerator,
        PdfGenerator $pdfGenerator,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$intervention->getInvoiceNumber()) {
            $intervention->setInvoiceNumber($numberGenerator->generateInvoiceNumber());
            $intervention->setInvoiceAt(new \DateTimeImmutable());
            $entityManager->flush();
        }

        $pdf = $pdfGenerator->generate('admin/pdf/invoice.html.twig', [
            'intervention' => $intervention,
        ]);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$intervention->getInvoiceNumber().'.pdf"',
        ]);
    }

    #[Route('/admin/intervention/{id}/devis-pdf', name: 'admin_intervention_quote_pdf')]
    public function quote(
        Intervention $intervention,
        DocumentNumberGenerator $numberGenerator,
        PdfGenerator $pdfGenerator,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$intervention->getQuoteNumber()) {
            $intervention->setQuoteNumber($numberGenerator->generateQuoteNumber());
            $intervention->setQuoteAt(new \DateTimeImmutable());
            $entityManager->flush();
        }

        $pdf = $pdfGenerator->generate('admin/pdf/quote.html.twig', [
            'intervention' => $intervention,
        ]);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$intervention->getQuoteNumber().'.pdf"',
        ]);
    }

    #[Route('/admin/intervention/{id}/rapport-pdf', name: 'admin_intervention_report_pdf')]
    public function report(
        Intervention $intervention,
        PdfGenerator $pdfGenerator
    ): Response {
        $pdf = $pdfGenerator->generate('admin/pdf/intervention_report.html.twig', [
            'intervention' => $intervention,
        ]);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="rapport-intervention-'.$intervention->getId().'.pdf"',
        ]);
    }
}
