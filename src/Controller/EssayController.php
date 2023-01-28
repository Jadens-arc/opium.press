<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Security\Core\User\UserInterface;

class EssayController extends AbstractController
{

    #[Route('/essay/share/{id}', name: 'app_share_essay')]
    public function share(Request $request, ManagerRegistry $doctrine, $id): Response
    {
        $post = $doctrine->getRepository(Post::class)->findOneBy(['id' => $id]);
        $pattern = "/[^_a-z0-9- ]/i";
        $title = preg_replace($pattern,'', $post->getTitle());
        $content = preg_replace($pattern,'', $post->getContent());
        $filename = $post->getCreatorId() . "-" . $post->getId(). ".png";
        $output = "hi";
        $code = null;
        $command = exec(
            "python3 /home/jaden/Opium-Scripts/main.py \"$filename\" \"$title\" \"$content\" 2>&1",
            $output,
            $code
        );
        return $this->render('essay/share.html.twig',
            ['post' => $filename, 'url' => $this->generateUrl("app_view_essay", ["id" => $id], UrlGenerator::ABSOLUTE_URL) ]);
    }

    #[Route('/essay/edit/{id}', name: 'app_new_essay')]
    public function newEssay(Request $request, ManagerRegistry $doctrine, UserInterface $user, $id): Response
    {
        $post = new Post();
        $post->setCreatorId($user->getId());

        $form = $this->createForm(PostType::class);
        if ($id != "new" && is_numeric($id)) {
            $post = $doctrine->getRepository(Post::class)->find($id);
            $form = $this->createForm(PostType::class, $post);
            if ($post->getTags())
                $form['tagInput']->setData(implode(",", $post->getTags()));
            if ($post->getSources())
                $form['sourceInput']->setData(implode(",", $post->getSources()));
        } else {
            $post->tagInput = "";
            $post->sourceInput = "";
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $userData = $em->find(User::class, $user->getId());
            $formData = $form->getData();
            if (!$request->query->get("isDraft")) {
                if (in_array("ROLE_ADMIN", $user->getRoles())) {
                    $post->setCreationDateAdmin();
                } else {
                        $post->setCreationDate();
                }
            }
            $post->setCreatorName($userData->getDisplayName());
            $post->setCreatorUsername($userData->getUsername());
            $post->setCreatorId($user->getId());
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

            if ($request->query->get("isDraft")) {
                $em->merge($post);
                $em->flush();
                return $this->redirectToRoute('app_drafts');
            } else {
                $em->persist($post);
                $em->flush();
                return $this->redirectToRoute('app_embargo');
            }
        }
        return $this->renderForm('essay/new.html.twig', ["form"=>$form]);
    }

    #[Route('/essay/delete/{id}', name: 'app_delete_essay')]
    public function delete(Request $request, ManagerRegistry $doctrine, UserInterface $user, $id): Response
    {
        $em = $doctrine->getManager();
        /**
         * @var Post $post
         */
        $post = $doctrine->getRepository(Post::class)->find($id);
        if (!$post)
            return $this->redirectToRoute('app_homepage', ['message' => "Post not Found", 'type' => 'danger']);
        if ($user->getId() != $post->getCreatorId()) {
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

    #[Route('/essay/post_draft/{id}', name: 'app_essay_post_draft')]
    public function postDraft(Request $request, ManagerRegistry $doctrine, UserInterface $user, $id): Response {
        $em = $doctrine->getManager();
        $post = $doctrine->getRepository(Post::class)->find($id);
        if (!$post)
            return $this->redirectToRoute('app_homepage', ['message' => "Post not Found", 'type' => 'danger']);
        if ($user->getId() != $post->getCreatorId())
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

    #[Route('/essay/{id}', name: 'app_view_essay')]
    public function index(Request $request, ManagerRegistry $doctrine, $id): Response
    {

        $currentDate = new DateTime();
        $currentDate->modify("-3 day");
        $currentDate = $currentDate->format('Y-m-d H:i:s');

        $post = $doctrine
            ->getManager()
            ->createQuery("SELECT p FROM App\Entity\Post p WHERE p.id = $id AND p.creationDate < '$currentDate'")
            ->getResult();

        if (!$post) {
            return $this->redirect('/');
        }
        return $this->renderForm('essay/index.html.twig', ["post" => $post[0]]);
    }
}
