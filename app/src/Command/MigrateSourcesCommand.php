<?php

namespace App\Command;

use App\Entity\Post;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-sources',
    description: 'Migrate sources to links',
)]
class MigrateSourcesCommand extends Command
{
    protected static $defaultDescription = 'Migrate sources to links';

    public function __construct(
        private EntityManagerInterface $em,
    ){
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $capsules = $this->em->getRepository(Post::class)->findAll();
        foreach ($capsules as $capsule) {
            /** @var Post $capsule*/
            $linkHtml = "<ul>";
            if (!$capsule->getSources()) continue;
            foreach ($capsule->getSources() as $source) {
                $linkHtml .= "<li><a href='$source'>$source</a></li>";
            }
            $linkHtml .= "</ul>";
            $capsule->setSources(null);
            $capsule->setContent($capsule->getContent() . $linkHtml);
            $this->em->persist($capsule);
        }
        $this->em->flush();


        $io->success('Migrated all sources');

        return Command::SUCCESS;
    }
}
