<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[ORM\Column(type: 'array', nullable: true)]
    private $tags = [];

    #[ORM\Column(type: 'text')]
    private $content;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $creationDate;

    #[ORM\Column(type: 'array', nullable: true)]
    private $sources = [];

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }
    
    public function isInEmbargo(): bool
    {
        $currentDate = new \DateTime();
        $currentDate->modify("-3 day");

        return ($this->creationDate > $currentDate);
    }

    public function setCreationDate(): self
    {
        $this->creationDate = new \DateTime("now");


        return $this;
    }

    public function setCreationDateAdmin(): self
    {
        $this->creationDate = new \DateTime("-3 day");

        return $this;
    }

    public function getSources(): ?array
    {
        return $this->sources;
    }

    public function setSources(?array $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }
}
