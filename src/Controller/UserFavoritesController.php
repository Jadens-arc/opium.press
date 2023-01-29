<?php

namespace App\Controller;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Post;
use App\Entity\User;

class UserFavoritesController extends AbstractController
{

    #[Route('/subscriptions', name: 'app_subscriptions')]
    public function subscriptions(Request $request, ManagerRegistry $doctrine, UserInterface $user): Response
    {
        $em = $doctrine->getManager();

        $id = $user->getId();
        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');
        
        $subscriptions = $em
            ->createQuery("SELECT u.subscriptions FROM App\Entity\User u WHERE u.id=$id")
            ->getResult();

        if (count($subscriptions[0]["subscriptions"]) == 0) {
            return $this->render('homepage/index.html.twig', ["title" => "Subscriptions", "posts"=>[]]);
        }

        $subscriptionsString = json_encode($subscriptions[0]["subscriptions"]);
        $subscriptionsString = str_replace("[", "( ", $subscriptionsString);
        $subscriptionsString = str_replace("]", " )", $subscriptionsString);
        $subscriptionsString = str_replace('"', "'", $subscriptionsString);
    

        $query = "SELECT p FROM App\Entity\Post p WHERE p.creationDate < '$currentDate' AND p.creator IN $subscriptionsString";
        $querySort = "ORDER BY p.creationDate DESC ";
        $query .= $querySort;
        $posts = $em
            ->createQuery($query)
            ->getResult();

        return $this->render('user_favorites/subscriptions.html.twig', ["title" => "Subscriptions", "posts"=>$posts, "subscriptions" => $subscriptions[0]["subscriptions"]]);
    }


    #[Route('/subscribe/{username}', name: 'app_subscribe')]
    public function subscribe(Request $request, ManagerRegistry $doctrine, UserInterface $user, $username): Response
    {
        $subscriptions = $user->getSubscriptions();
        $userId = $doctrine->getRepository(User::class)->findOneBy(['username' => $username])->getId();
        array_push($subscriptions, $userId);
        $user->setSubscriptions($subscriptions);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('app_writer', ["username" => $username]);
    }

    #[Route('/unsubscribe/{username}', name: 'app_unsubscribe')]
    public function unsubscribe(Request $request, ManagerRegistry $doctrine, UserInterface $user, $username): Response
    {
        $subscriptions = $user->getSubscriptions();
        $index = array_search($username, $subscriptions);
        unset($subscriptions[$index]);
        $user->setSubscriptions($subscriptions);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('app_writer', ["username" => $username]);
    }


    #[Route('/saved', name: 'app_saved')]
    public function saved(Request $request, ManagerRegistry $doctrine, UserInterface $user): Response
    {
        $currentDate = new DateTime();
        $currentDate->modify("-2 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');

        $em = $doctrine->getManager();
        $id = $user->getId();

        $saved = $em
            ->createQuery("SELECT u.saved FROM App\Entity\User u WHERE u.id=$id")
            ->getResult();

        if (count($saved[0]["saved"]) == 0) {
            return $this->render('homepage/index.html.twig', ["title" => "Saved", "posts"=>[]]);
        }

        $savedString = json_encode($saved[0]["saved"]);
        $savedString = str_replace("[", "( ", $savedString);
        $savedString = str_replace("]", " )", $savedString);
        $savedString = str_replace('"', "'", $savedString);

        $query = "SELECT p FROM App\Entity\Post p WHERE p.creationDate < '$currentDate' AND p.id IN $savedString";
        $querySort = "ORDER BY p.creationDate DESC ";
        $query .= $querySort;
        $posts = $em
            ->createQuery($query)
            ->getResult();


        return $this->render('homepage/index.html.twig', ["title" => "Saved", "posts"=>$posts]);
    }

    #[Route('/save/{id}', name: 'app_save')]
    public function save(Request $request, ManagerRegistry $doctrine, UserInterface $user, $id): Response
    {
        $saved = $user->getSaved();
        if (in_array($id, $saved)) {
            return $this->redirect($request->headers->get('referer'));
        }
        array_push($saved, $id);
        $user->setSaved($saved);
        $em = $doctrine->getManager();
        $em->persist($user);
        $em->flush();
        return $this->redirect($request->headers->get('referer'));
    }
}
