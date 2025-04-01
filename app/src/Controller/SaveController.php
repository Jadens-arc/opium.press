<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Save;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
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

        /** @var array $saves */
        $saves = $em->getRepository(Save::class)
            ->createQueryBuilder('s')
            ->select('s')
            ->where('s.user = :creator')
            ->setParameter('creator', $user)
            ->orderBy('s.creationDate', 'desc')
            ->getQuery()
            ->getResult()
        ;

        $saveIds = [];

        foreach($saves as $save) {
            /** @var Save $save */
            $saveIds[] = $save->getPost()->getId();
        }

        $posts = $em->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p in (:saves)')
            ->leftJoin(Save::class, 's', Join::WITH, 's.post = p.id')
            ->orderBy('s.creationDate', 'desc')
            ->setParameter('saves', $saveIds);


        $adapter = new QueryAdapter($posts);
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
    #[Route('/save/{uuid}', name: 'app_save')]
    public function save(UrlGeneratorInterface $urlGenerator, UserInterface $user, ManagerRegistry $doctrine, $uuid): Response
    {
        /** @var User $user */
        $post = $doctrine->getRepository(Post::class)->findOneBy(['uuid' => $uuid]);
        if (in_array($post, $user->getSavedPosts()))
            return $this->redirectToRoute('app_saves', [], 302, "#" . $uuid);
        $newSave = new Save();
        $newSave->setCreationDate();
        $newSave->setUser($user);
        $newSave->setPost($doctrine->getRepository(Post::class)->findOneBy(['uuid' => $uuid]));
        $doctrine->getManager()->persist($newSave);
        $user->addSave($newSave);
        $doctrine->getManager()->persist($user);
        $doctrine->getManager()->flush();

        return $this->redirectToRoute("app_saves");
    }

    #[Route('/unsave/{uuid}', name: 'app_unsave')]
    public function unsave(UserInterface $user, ManagerRegistry $doctrine, $uuid): Response
    {
        $em = $doctrine->getManager();
        $post = $em->getRepository(Post::class)->findOneBy(['uuid' => $uuid]);
        $save = $doctrine->getRepository(Save::class)->findOneBy(["post" => $post, "user" => $user->getId()]);
        if ($save) {
            $em->remove($save);
            $em->flush();
        }

        return $this->redirectToRoute("app_saves");
    }
}
