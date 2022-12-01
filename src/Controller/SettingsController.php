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

    #[Route('/delete-account/{id}', name: 'app_delete_account')]
    public function deleteAccount(ManagerRegistry $doctrine, UserInterface $user, int $id): Response
    {
        if ($user->getId() != $id) {
            return $this->redirectToRoute('app_settings');
        }
        $em = $doctrine->getManager();
        $user_posts = $doctrine->getRepository(Post::class)->findBy(['creatorId' => $user->getId()]);
        if ($user_post) {
            foreach ($user_post as $post) {
                $em->remove($post);
                $em->flush();
            }
        }
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => $user->getId()]);
        $em->remove($user);
        $em->flush();
        return $this->redirectToRoute('app_homepage');
    }

}
