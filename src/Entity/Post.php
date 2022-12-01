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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $creatorName;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $creatorId;

    #[ORM\Column(type: 'text')]
    private $content;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $creationDate;

    #[ORM\Column(type: 'array', nullable: true)]
    private $sources = [];

    #[ORM\Column(type: 'string', length: 255)]
    private $creatorUsername;

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

    public function getCreatorName(): ?string
    {
        return $this->creatorName;
    }

    public function setCreatorName(string $creatorName): self
    {
        $this->creatorName = $creatorName;

        return $this;
    }

    public function getCreatorId(): ?int
    {
        return $this->creatorId;
    }

    public function setCreatorId(int $creatorId): self
    {
        $this->creatorId = $creatorId;

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
        $currentDate = $currentDate->format('Y-m-d H:i:s');

        return ($this.creationDate < $currentDate);
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

    public function getCreatorUsername(): ?string
    {
        return $this->creatorUsername;
    }

    public function setCreatorUsername(string $creatorUsername): self
    {
        $this->creatorUsername = $creatorUsername;

        return $this;
    }
}
