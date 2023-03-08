<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        $writer = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$writer) {
            return $this->redirect('/');
        }
        $qb = $doctrine->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.creationDate < :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->andWhere('p.creator = :writer')
            ->setParameter('writer', $writer)
            ->orderBy('p.creationDate', 'DESC')
            ->getQuery();

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);

        if (isset($_GET["page"])) {
            $pagerfanta->setCurrentPage($_GET["page"]);
        }
        return $this->render('writer/index.html.twig', [
            'posts' => $pagerfanta->getCurrentPageResults(),
            'pager' => $pagerfanta,
            'writer' => $writer
        ]);
    }

    #[Route('/drafts', name: "app_drafts")]
    public function drafts(Request $request, ManagerRegistry $doctrine, UserInterface $user): Response {
        $qb = $doctrine->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.creator = :user')
            ->setParameter('user', $user)
            ->andWhere('p.creationDate is null')
            ->orderBy('p.creationDate', 'DESC')
        ;

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);

        if (isset($_GET["page"])) {
            $pagerfanta->setCurrentPage($_GET["page"]);
        }
        return $this->render('writer/drafts.html.twig', [
            "posts" => $pagerfanta->getCurrentPageResults(),
            "title" => "Drafts",
            "pager" => $pagerfanta
        ]);
    }
}
