<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation\Relation;
use Hateoas\Configuration\Annotation\Route;
use Hateoas\Configuration\Annotation\Exclusion;

#[Relation(
    name: "self",
    href: new Route(
        name: "app_detail_user",
        parameters: ["id" => "expr(object.getId())"]
    ),
    exclusion: new Exclusion(groups: ["getUsers"])
)]
#[Relation(
    name: "delete",
    href: new Route(
        name: "app_delete_user",
        parameters: ["id" => "expr(object.getId())"]
    ),
    exclusion: new Exclusion(groups: ["getUsers"])
)]
#[Relation(
    name: "create",
    href: new Route(
        name: "app_create_user"
    ),
    exclusion: new Exclusion(groups: ["getUsers"])
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getUsers", "getClients"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getClients"])]
    #[Assert\NotBlank(message: "Le nom de l'utilisateur est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom de l'utilisateur doit faire au moins {{ limit }} caractère", maxMessage: "Le nom de l'auteur ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getUsers", "getClients"])]
    #[Assert\NotBlank(message: "L'email de l'utilisateur est obligatoire")]
    #[Assert\Email(message: "L'email {{ value }} n'est pas valide")]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(["getUsers"])]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getUsers"])]
    #[Assert\NotBlank(message: "L'id du client est obligatoire")]
    private ?Client $client = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }
}
