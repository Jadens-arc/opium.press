<?php

namespace App\Controller;

use App\Entity\Post;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
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

        $qb = $doctrine->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.creator = :user')
            ->setParameter("user", $user)
            ->andWhere('p.creationDate > :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->orderBy('p.creationDate', 'DESC')
        ;

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);

        if (isset($_GET["page"])) {
            $pagerfanta->setCurrentPage($_GET["page"]);
        }

        return $this->render('embargo/index.html.twig', ["posts"=>$pagerfanta->getCurrentPageResults(), 'pager' => $pagerfanta]);
    }
}
