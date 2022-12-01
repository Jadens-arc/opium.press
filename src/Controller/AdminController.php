<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UserInterface $user): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(UserInterface $user, ManagerRegistry $doctrine): Response
    {
        $query = "SELECT u FROM App\Entity\User u";
        $rows = $doctrine
            ->getManager()
            ->createQuery($query)
            ->getResult();

        return $this->render('admin/users.html.twig', [
            'rows' => $rows
        ]);
    }

    #[Route('/admin/posts', name: 'app_admin_posts')]
    public function posts(UserInterface $user, ManagerRegistry $doctrine): Response
    {
        $query = "SELECT p FROM App\Entity\Post p";
        $rows = $doctrine
            ->getManager()
            ->createQuery($query)
            ->getResult();

        return $this->render('admin/posts.html.twig', [
            'rows' => $rows
        ]);
    }
}
