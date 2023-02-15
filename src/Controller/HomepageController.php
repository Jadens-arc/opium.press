<?php

namespace App\Controller;

use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Post;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');

        $searchQuery = $request->query->get('q');


        $qb = $doctrine->getRepository(Post::class)
            ->createQueryBuilder('p');

        if ($searchQuery) { // if user is searching
            $rawSearch = substr($searchQuery, 1); // remove @ and #
            if (strpos($searchQuery, "#") !== false) { // searching for hashtags
                $qb->where("p.tags like :search")
                    ->setParameter('search', $rawSearch);
            } elseif (strpos($searchQuery, "@") !== false) { // searching for usernames
                $qb->where("p.creator.username like :username")
                    ->setParameter('username', $rawSearch);
            } else { // just searching by content and title
                $qb->where("p.content like :search")
                    ->orWhere("p.title like :search")
                    ->setParameter('search', "%".$searchQuery."%");
            }
        }

        $qb->andWhere('p.creationDate < :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->orderBy('p.creationDate', 'DESC')
        ;

        $posts = $qb->getQuery()->getResult();
        return $this->render('homepage/index.html.twig', ["title" => "Latest...",  "posts"=>$posts]);
    }

}
