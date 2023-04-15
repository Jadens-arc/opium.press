<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Save;
use App\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use http\Env\Request;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SaveController extends AbstractController
{
    #[Route('/saves', name: 'app_saves')]
    public function index(UserInterface $user, ManagerRegistry $doctrine): Response
    {
        /** @var User $user */
        $em = $doctrine->getManager();


        $repo = $em->getRepository(Post::class);
        $qb = $repo->createQueryBuilder('p')
            ->where('p.creator = :creator')
            ->setParameter('creator', $user)
            ->innerJoin("p.saves", "s", Join::WITH, "s.user = :user")
            ->setParameter("user", $user)
            ->orderBy("s.creationDate", "desc")
            ->getQuery();

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);

        if (isset($_GET["page"])) {
            $pagerfanta->setCurrentPage($_GET["page"]);
        }

        return $this->render('save/index.html.twig', [
            "title" => "Saves",
            "posts" => $pagerfanta->getCurrentPageResults(),
            "pager" => $pagerfanta,
        ]);
    }
    #[Route('/save/{id}', name: 'app_save')]
    public function save(UrlGeneratorInterface $urlGenerator, UserInterface $user, ManagerRegistry $doctrine, $id): Response
    {
        /** @var User $user */
        $newSave = new Save();
        $newSave->setCreationDate();
        $newSave->setUser($user);
        $newSave->setPost($doctrine->getRepository(Post::class)->find($id));
        $doctrine->getManager()->persist($newSave);
        $user->addSave($newSave);
        $doctrine->getManager()->persist($user);
        $doctrine->getManager()->flush();

        return $this->redirectToRoute("app_saves");
    }

    #[Route('/unsave/{id}', name: 'app_unsave')]
    public function unsave(UserInterface $user, ManagerRegistry $doctrine, $id): Response
    {
        $em = $doctrine->getManager();
        $save = $doctrine->getRepository(Save::class)->findOneBy(["post" => $id, "user" => $user->getId()]);
        $em->remove($save);
        $em->flush();

        return $this->redirectToRoute("app_saves");
    }
}
