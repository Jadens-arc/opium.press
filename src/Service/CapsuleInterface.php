<?php
namespace App\Service;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class CapsuleInterface
{
    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     *
     * @param User $user
     * @param String $title
     * @param String $content
     * @param array $tags
     * @param array $sources
     * @return void
     */
    public function postNew(User $user, String $title, String $content, Array $tags, Array $sources): void {
        $post = new Post();
        $post->setCreator($user);
        $post->setTitle($title);
        $post->setContent($content);
        $post->setTags($tags);
        $post->setSources($sources);
        if (in_array("ROLE_ADMIN", $user->getRoles())) {
            $post->setCreationDateAdmin();
        } else {
            $post->setCreationDate();
        }
        $em = $this->doctrine->getManager();
        $em->persist($post);
        $em->flush();
    }

    /**
     * Take an existing post (from drafts) and put it in the embargo
     * @param Post $post
     * @return void
     */
    public function postExisting(Post $post):void {
        if (in_array('ROLE_ADMIN', $post->getCreator()->getRoles())) {
            $post->setCreationDateAdmin();
        } else {
            $post->setCreationDate();
        }
    }

    public function getHappyMessage(): string
    {
        $messages = [
            'You did it! You updated the system! Amazing!',
            'That was one of the coolest updates I\'ve seen all day!',
            'Great work! Keep going!',
        ];

        $index = array_rand($messages);
        return $messages[$index];
    }
}
