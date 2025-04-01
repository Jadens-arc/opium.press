<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Service\ImageGenerator;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
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

    #[Route('/writer/share/{username}', name: "app_share_writer")]
    public function share(Request $request, ManagerRegistry $doctrine, ImageGenerator $imageGenerator, $username): Response {
        $user = $doctrine->getRepository(User::class)->findOneBy(['username' => $username]);
        $filepath = $imageGenerator->writerStory($user);
        return $this->render('writer/share.html.twig', [
            'src' => $filepath,
            'writer' => $user,
            'url' => $this->generateUrl('app_writer', ['username' => $username], UrlGenerator::ABSOLUTE_URL)
        ]);

    }

    #[Route('/drafts', name: "app_drafts")]
    public function drafts(Request $request, ManagerRegistry $doctrine, UserInterface $user): Response {
        $qb = $doctrine->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.creator = :user')
            ->setParameter('user', $user)
            ->andWhere('p.creationDate is null')
            ->orderBy('p.id', 'DESC')
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
