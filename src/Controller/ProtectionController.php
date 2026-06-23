<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProtectionController extends AbstractController
{
    #[Route('/protection', name: 'app_protection')]
    public function index(): Response
    {
        return $this->render('protection/index.html.twig', [
            'controller_name' => 'ProtectionController',
        ]);
    }
}
