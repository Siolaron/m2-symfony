<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $grid = [];

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'games')]
    private Collection $players;

    #[ORM\ManyToOne(inversedBy: 'games')]
    private ?User $lastMove = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    private ?User $winner = null;

    public function __construct()
    {
        for($i = 0; $i < 6; $i++){
            for($j = 0; $j < 7; $j++){
                $board[$i][$j] = '';
            }
        }
        $this->setGrid($board);
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrid(): array
    {
        return $this->grid;
    }

    public function setGrid(array $grid): self
    {
        $this->grid = $grid;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(User $player): self
    {
        if($this->getPlayers()->count() < 2 ){
            if (!$this->players->contains($player)) {
                $this->players->add($player);
            }
        }
        return $this;
    }

    public function removePlayer(User $player): self
    {
        $this->players->removeElement($player);

        return $this;
    }

    public function getLastMove(): ?User
    {
        return $this->lastMove;
    }

    public function setLastMove(?User $lastMove): self
    {
        $this->lastMove = $lastMove;

        return $this;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): self
    {
        $this->winner = $winner;

        return $this;
    }
}
