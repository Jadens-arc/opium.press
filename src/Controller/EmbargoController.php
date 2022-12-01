<?php

namespace App\Controller;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;


class EmbargoController extends AbstractController
{
    #[Route('/embargo', name: 'app_embargo')]
    public function index(ManagerRegistry $doctrine, UserInterface $user): Response
    {
        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            return $this->redirectToRoute("app_homepage");
        }
        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');
        $id = $user->getId();
        $posts = $doctrine
            ->getManager()
            ->createQuery("SELECT p FROM App\Entity\Post p WHERE p.creatorId = $id AND p.creationDate > '$currentDate' ORDER BY p.creationDate DESC")
            ->getResult();
        return $this->render('embargo/index.html.twig', ["posts"=>$posts]);
    }
}
