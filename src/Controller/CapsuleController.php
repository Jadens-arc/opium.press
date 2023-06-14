<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use App\Service\ImageGenerator;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
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
        $post = new Post();
        $post->setCreator($user);

        $data=[];

        $form = $this->createForm(PostType::class);
        if ($uuid != "new") { // editing draft
            $post = $doctrine->getRepository(Post::class)->findOneBy(['uuid' => $uuid]);
            $post->setCreator($user);
            $form = $this->createForm(PostType::class, $post);
            if ($post->getTags())
                $form['tagInput']->setData(implode(",", $post->getTags()));
            if ($post->getSources())
                $form['sourceInput']->setData(implode(",", $post->getSources()));
            if ($post->getReply()) {
                $reply = $post->getReply();
                $form['reply']->setData($reply->getId());
                $data['replying-to'] = $reply;
            }
        } else { // completely new post
            $post->tagInput = "";
            $post->sourceInput = "";
            $post->setUuid(UUID::v1());
            if ($request->get("replying-to")) {
                $reply = $doctrine->getRepository(Post::class)->findOneBy(
                    ['uuid' => $request->get("replying-to")]
                );
                $post->setReply($reply);
                $form['reply']->setData($reply->getId());
                $data['replying-to'] = $reply;
            }
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $userData = $em->find(User::class, $user->getId());
            $formData = $form->getData();
            if (!$form['isDraft']->getData()) {
                if (in_array("ROLE_ADMIN", $user->getRoles())) {
                    $post->setCreationDateAdmin();
                } else {
                        $post->setCreationDate();
                }
            }
            $post->setCreator($user);
            $tagInput = $form['tagInput']->getData();
            if (strlen(trim($tagInput)) > 0) {
                $tags = $tagInput;
                if (!in_array("ROLE_ADMIN", $user->getRoles())) {
                    $tags = str_replace("haute maison", "", str_replace("haute maison,", "", $tagInput));
                }
                $post->setTags(explode(",", $tags));
            } else {
                $post->setTags([]);
            }
            if (in_array("ROLE_ADMIN", $user->getRoles())) {
                $newTags = $post->getTags();
                array_push($newTags, "haute maison");
                $post->setTags($newTags);
            }
            $sourceInput = $form['sourceInput']->getData();
            if (strlen(trim($sourceInput)) > 0) {
                $post->setSources(explode(",", $sourceInput));
            }
            if ($form['reply']->getData())
                $post->setReply($em->getRepository(Post::class)->find($form['reply']->getData()));

            $post->setTitle($form['title']->getData());
            $post->setContent($form['content']->getData());

            if ($form['isDraft']->getData()) {
                $em->merge($post);
                $em->flush();
                return $this->redirectToRoute('app_drafts');
            }
            $em->persist($post);
            $em->flush();
            return $this->redirectToRoute('app_embargo');
        }
        return $this->renderForm('capsule/new.html.twig', ["form"=>$form, "data" => $data]);
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
