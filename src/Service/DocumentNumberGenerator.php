<?php

namespace App\Service;

use App\Repository\InterventionRepository;

class DocumentNumberGenerator
{
    public function __construct(
        private InterventionRepository $interventionRepository
    ) {
    }

    public function generateQuoteNumber(): string
    {
        return $this->generateNumber('DEV');
    }

    public function generateInvoiceNumber(): string
    {
        return $this->generateNumber('FAC');
    }

    private function generateNumber(string $prefix): string
    {
        $year = date('Y');

        $lastNumber = $this->interventionRepository->findLastDocumentNumber($prefix, $year);

        $nextNumber = 1;

        if ($lastNumber) {
            $parts = explode('-', $lastNumber);
            $nextNumber = (int) end($parts) + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }
}