<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function index(): Response
    {
        return $this->render('index.html.twig', [
            'controller_name' => 'MainController',
        ]);
    }
}
