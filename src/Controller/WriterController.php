<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;


class WriterController extends AbstractController
{

    #[Route('/writer/{username}', name: 'app_writer')]
    public function writer(ManagerRegistry $doctrine, $username): Response
    {
        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');
        $user = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user) {
            return $this->redirect('/');
        }
        $id = $user->getId();
        $posts = $doctrine
            ->getManager()
            ->createQuery("SELECT p FROM App\Entity\Post p WHERE p.creationDate < '$currentDate' and p.creatorId = $id ORDER BY p.creationDate DESC")
            ->getResult();
        return $this->render('writer/index.html.twig', [
            'posts' => $posts,
            'username' => $user->getUsername(),
            'displayName' => $user->getDisplayName(),
            'email' => $user->getEmail(),
        ]);
    }

}
