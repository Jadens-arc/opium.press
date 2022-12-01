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
        $query = "SELECT p FROM App\Entity\Post p WHERE p.creationDate < '$currentDate'";
        $querySort = "ORDER BY p.creationDate DESC";

        if ($searchQuery) { // if user is searching
            $rawSearch = substr($searchQuery, 1); // remove @ and #
            if (strpos($searchQuery, "#") !== false) { // searching for hashtags
                $query .= "AND p.tags like '%$rawSearch%' $querySort";
            }elseif (strpos($searchQuery, "@") !== false) { // searching for usernames
                $query .= " AND (p.content like '% $searchQuery %' OR p.creatorUsername = '$rawSearch') $querySort";
            }else { // just searching by content and title
                $query .= " AND (p.content like '% $searchQuery %' OR p.title like '% $searchQuery %') $querySort";
            }
        } else { // if user is not searching
            $query .= $querySort;
        }

        $posts = $doctrine
            ->getManager()
            ->createQuery($query)
            ->getResult();

        return $this->render('homepage/index.html.twig', ["title" => "Latest...",  "posts"=>$posts]);
    }

}
