<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:retro-uuid')]
class retroUUID extends Command
{
    protected static $defaultDescription = 'Retroactively Generates UUID\'s for old posts';

    public function __construct(
        private EntityManagerInterface $em,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
    // ... put here the code to create the user
        $posts = $this->em->getRepository(Post::class)->findAll();
        foreach($posts as $post) {
            /** @var Post $post */
            $post->setUuid(UUID::v1());
            $this->em->persist($post);
        }
        $this->em->flush();

        return Command::SUCCESS;
    }
}