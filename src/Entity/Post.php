<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    private ?self $reply = null;

    #[ORM\OneToMany(mappedBy: 'reply', targetEntity: self::class)]
    private Collection $replies;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Save::class, orphanRemoval: true)]
    private Collection $saves;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->saves = new ArrayCollection();
    }

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

    public function timeUntilPublic(): string
    {
        /** @var \DateTime $publicDate */
        $publicDate = clone $this->creationDate;
        $publicDate->modify("+3 day");
        $currentDate = new \DateTime();
        $difference = $publicDate->diff($currentDate);

        return $difference->format("%d days and %h hours");
    }

    public function percentUntilPublic(): float
    {
        /** @var \DateTime $publicDate */
        $publicDate = clone $this->creationDate;
        $publicDate->modify("+3 day");
        $currentDate = new \DateTime();
        $difference = $publicDate->diff($currentDate);
        return 1 - ((($difference->format('%a') * 1440) + ($difference->format('%h') * 60) + $difference->format('%i')) / 4320);
    }

    public function setCreationDate(?\Datetime $datetime = new \DateTime("now")): self
    {
        $this->creationDate = $datetime;


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

    public function getReply(): ?self
    {
        return $this->reply;
    }

    public function setReply(?self $reply): self
    {
        $this->reply = $reply;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(self $reply): self
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setReply($this);
        }

        return $this;
    }

    public function removeReply(self $reply): self
    {
        if ($this->replies->removeElement($reply)) {
            // set the owning side to null (unless already changed)
            if ($reply->getReply() === $this) {
                $reply->setReply(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Save>
     */
    public function getSaves(): Collection
    {
        return $this->saves;
    }

    public function addSave(Save $save): self
    {
        if (!$this->saves->contains($save)) {
            $this->saves->add($save);
            $save->setPost($this);
        }

        return $this;
    }

    public function removeSave(Save $save): self
    {
        if ($this->saves->removeElement($save)) {
            // set the owning side to null (unless already changed)
            if ($save->getPost() === $this) {
                $save->setPost(null);
            }
        }

        return $this;
    }
}
