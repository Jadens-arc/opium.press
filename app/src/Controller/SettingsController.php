<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Post;
use App\Entity\User;

class SettingsController extends AbstractController
{
    #[Route('/settings', name: 'app_settings')]
    public function index(): Response
    {
        return $this->render('settings/index.html.twig', [
            'controller_name' => 'AboutController',
        ]);
    }
    #[Route('/terms-and-conditions', name: 'app_terms')]
    public function terms(ManagerRegistry $doctrine): Response
    {
        return $this->render('settings/terms-and-conditions.html.twig');
    }
    #[Route('/privacy-policy', name: 'app_privacy')]
    public function privacy(ManagerRegistry $doctrine): Response
    {
        return $this->render('settings/privacy-policy.html.twig');
    }
    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('settings/about.html.twig');
    }
}
