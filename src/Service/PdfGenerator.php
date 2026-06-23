<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PdfGenerator extends AbstractController
{
    public function generate(string $template, array $data = []): string
    {
        $options = new Options();

        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('chroot', $this->getParameter('kernel.project_dir') . '/public');

        $dompdf = new Dompdf($options);

        $data['public_path'] = $this->getParameter('kernel.project_dir') . '/public';

        $html = $this->renderView($template, $data);

        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        return $dompdf->output();
    }
}
