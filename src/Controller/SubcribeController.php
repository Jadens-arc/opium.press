<?php

namespace App\Controller;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Post;
use App\Entity\User;

class SubcribeController extends AbstractController
{

    #[Route('/subscriptions', name: 'app_subscriptions')]
    public function subscriptions(Request $request, ManagerRegistry $doctrine, UserInterface $user): Response
    {
        $em = $doctrine->getManager();

        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');

        $repo = $em->getRepository(Post::class);
        $qb = $repo->createQueryBuilder('p')
            ->where('p.creationDate < :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->andWhere('p.creator in (:subscriptions)')
            ->setParameter('subscriptions', $user->getSubscriptions())
            ->orderBy('p.creationDate', 'DESC')
            ->getQuery();

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);

        if (isset($_GET["page"])) {
            $pagerfanta->setCurrentPage($_GET["page"]);
        }

        return $this->render('subscriptions/subscriptions.html.twig', [
            "title" => "Subscriptions",
            "posts" => $pagerfanta->getCurrentPageResults(),
            "pager" => $pagerfanta,
            "subscriptions" => $user->getSubscriptions()
        ]);
    }


    #[Route('/subscribe/{username}', name: 'app_subscribe')]
    public function subscribe(Request $request, ManagerRegistry $doctrine, UserInterface $user, $username): Response
    {
        $em = $doctrine->getManager();
        $writer = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);
        $user->addSubscription($writer);
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('app_writer', ["username" => $username]);
    }

    #[Route('/unsubscribe/{username}', name: 'app_unsubscribe')]
    public function unsubscribe(Request $request, ManagerRegistry $doctrine, UserInterface $user, $username): Response
    {
        $em = $doctrine->getManager();
        $writer = $em->getRepository(User::class)->findOneBy(['username' => $username]);
        $user->removeSubscription($writer);
        $em->persist($user);
        $em->flush();
        return $this->redirectToRoute('app_writer', ["username" => $username]);
    }
}
