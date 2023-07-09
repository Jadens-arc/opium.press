<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Service\ImageGenerator;
use DateTime;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class CapsuleController extends AbstractController
{

    #[Route('/capsule/share/{uuid}', name: 'app_share_capsule')]
    public function share(Request $request, ManagerRegistry $doctrine, ImageGenerator $imageGenerator, $uuid): Response
    {
        $post = $doctrine->getRepository(Post::class)->findOneBy(["uuid" => $uuid]);
        $filepath = $imageGenerator->capsuleStory($post);
        return $this->render('capsule/share.html.twig', [
            'src' => $filepath,
            'url' => $this->generateUrl('app_view_capsule', ['uuid' => $uuid], UrlGenerator::ABSOLUTE_URL)
        ]);
    }

    #[Route('/capsule/edit/{uuid}', name: 'app_new_capsule')]
    public function newCapsule(Request $request, ManagerRegistry $doctrine, UserInterface $user, $uuid): Response
    {
        $post = $uuid != "new" ? $doctrine->getRepository(Post::class)->findOneBy(['uuid' => Uuid::fromString($uuid)->toBinary()]) : new Post();
        if (!$post->getCreator()) $post->setCreator($user);
        if ($post->getCreator() != $user || $post->getCreationDate()) return $this->redirectToRoute('app_homepage');
        if ($request->get("replying-to"))
            $post->setReply($doctrine->getRepository(Post::class)->findOneBy(
                ['uuid' => Uuid::fromString($request->get('replying-to'))->toBinary()])
            );

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isDraft = $form->get("saveToDrafts")->isClicked();
            if (!$isDraft) $post->setCreationDate(); // creation date will be null if post is draft

            // Handle Admin Stuff
            if (in_array(User::$ROLE_ADMIN, $user->getRoles())) {
                $post->setCreationDate(new \DateTime("-3 days"));
                if (!in_array("haute maison", $post->getTags())) {
                    $tags = $post->getTags();
                    $tags[] = "haute maison";
                    $post->setTags($tags);
                }
            }

            $doctrine->getManager()->persist($post);
            $doctrine->getManager()->flush();

            return $this->redirectToRoute($isDraft ? "app_drafts" : "app_embargo");
        }

        return $this->renderForm('capsule/new.html.twig', ["form" => $form, "post" => $post]);
    }

    #[Route('/capsule/delete/{uuid}', name: 'app_delete_capsule')]
    public function delete(Request $request, ManagerRegistry $doctrine, UserInterface $user, $uuid): Response
    {
        $em = $doctrine->getManager();
        /**
         * @var Post $post
         */
        $post = $doctrine->getRepository(Post::class)->findOneBy(['uuid' => $uuid]);
        if (!$post)
            return $this->redirectToRoute('app_homepage', ['message' => "Post not Found", 'type' => 'danger']);
        if ($user !== $post->getCreator()) {
            return $this->redirectToRoute('app_homepage', ['message' => "Not your post", 'type' => 'danger']);
        }
        if ($post->isInEmbargo() || !$post->getCreationDate() || in_array("ROLE_ADMIN", $user->getRoles())) {
            $em->remove($post);
            $em->flush();
            if (!$post->getCreationDate()) {
                return $this->redirectToRoute('app_drafts');
            }
            return $this->redirectToRoute('app_embargo');
        }
        return $this->redirectToRoute('app_homepage');
    }

    #[Route('/capsule/revert/{uuid}', name: 'app_revert_capsule')]
    public function revert(Request $request, ManagerRegistry $doctrine, UserInterface $user, $uuid): Response
    {
        $em = $doctrine->getManager();
        /**
         * @var Post $post
         */
        $post = $doctrine->getRepository(Post::class)->findOneBy(['uuid' => $uuid]);
        if (!$post)
            return $this->redirectToRoute('app_homepage', ['message' => "Post not Found", 'type' => 'danger']);
        if ($user !== $post->getCreator()) {
            return $this->redirectToRoute('app_homepage', ['message' => "Not your post", 'type' => 'danger']);
        }
        if ($post->isInEmbargo() || in_array("ROLE_ADMIN", $user->getRoles())) {
            $post->setCreationDate(NULL);
            $em->persist($post);
            $em->flush();
            if (!$post->getCreationDate()) {
                return $this->redirectToRoute('app_drafts');
            }
            return $this->redirectToRoute('app_drafts');
        }
        return $this->redirectToRoute('app_homepage');
    }


    #[Route('/capsule/replies/{uuid}', name: 'app_capsule_replies')]
    public function replies(Request $request, ManagerRegistry $doctrine, UserInterface $user, $uuid): Response
    {
        $post = $doctrine->getRepository(Post::class)->findOneBy(["uuid" => UUid::fromString($uuid)->toBinary()]);


        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');

        $posts = $doctrine->getRepository(Post::class)->createQueryBuilder('p')
            ->where('p.reply = :post')
            ->andWhere('p.creationDate < :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->setParameter('post', $post);


        $adapter = new QueryAdapter($posts);
        $pagerfanta = new Pagerfanta($adapter);

        $title =  "Latest Capsules... ";

        if (isset($_GET["page"])) {
            $pagerfanta->setCurrentPage($_GET["page"]);
            $title = " Page " . $_GET["page"] . " of " . $title;
        }

        return $this->render('capsule/replies.html.twig', ["title" => "Showing Replies",  "posts"=>$pagerfanta->getCurrentPageResults(), 'pager' => $pagerfanta]);
    }

    #[Route('/capsule/post_draft/{uuid}', name: 'app_capsule_post_draft')]
    public function postDraft(Request $request, ManagerRegistry $doctrine, UserInterface $user, $uuid): Response {
        $em = $doctrine->getManager();
        $post = $doctrine->getRepository(Post::class)->findOneBy(['uuid' => $uuid]);
        if (!$post)
            return $this->redirectToRoute('app_homepage', ['message' => "Post not Found", 'type' => 'danger']);
        if ($user !== $post->getCreator())
            return $this->redirectToRoute('app_homepage', ['message' => "You don't own this", 'type' => 'danger']);
        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            $post->setCreationDateAdmin();
            return $this->redirectToRoute("app_homepage");
        } else {
            $post->setCreationDate();
            $em->merge($post);
            $em->flush();
            return $this->redirectToRoute("app_embargo");
        }
    }

    #[Route('/capsule/{uuid}', name: 'app_view_capsule')]
    public function index(Request $request, ManagerRegistry $doctrine, UserInterface $user=null, $uuid): Response
    {

        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');


        $post = $doctrine->getRepository(Post::class)
            ->createQueryBuilder('p')
            ->where('p.uuid = :uuid')
            ->setParameter("uuid", UUid::fromString($uuid)->toBinary())
            ->andWhere('p.creationDate < :currentDate or p.creator = :user')
            ->setParameter("currentDate", $currentDate)
            ->setParameter("user", $user)
            ->getQuery()
            ->getResult()
        ;

//        if (!$post) {
//            return $this->redirect('/');
//        }
        return $this->renderForm('capsule/index.html.twig', ["post" => $post[0]]);
    }
}
