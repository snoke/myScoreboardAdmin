<?php

namespace App\Entity;

use App\Annotations\Input;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MatchRepository")
 * @ORM\Table(name="`match`")
 */
class Match extends Entity
{
    /**
	 * @Input(groupBy="league")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
	 * @Input
	 * @Assert\Expression(
	 *     "true==this.getLeague().isFull()",
	 *     message="Es fehlen noch Mannschaften in der Liga!"
	 * )
     * @ORM\ManyToOne(targetEntity="App\Entity\League", inversedBy="matches")
     * @ORM\JoinColumn(nullable=false)
     */
    private $league;

	
    /**
     * @Assert\Expression(
     *     "true == this.getTeam().isMyTurn()",
     *     message="Die Mannschaft kann erst am nächsten Spieltag wieder spielen!"
     * )
     * @Assert\Expression(
     *     "this.getTeam().getLeague() === this.getLeague()",
     *     message="Die Mannschaft befindet sich nicht in der Liga!"
     * )
	 * @Input(groupBy="league")
     * @ORM\ManyToOne(targetEntity="App\Entity\Team")
     * @ORM\JoinColumn(nullable=false)
     */
    private $team;

    /**
     * @Assert\Expression(
     *     "true == this.getOpponent().isMyTurn()",
     *     message="Die Mannschaft kann erst am nächsten Spieltag wieder spielen!"
     * )
     * @Assert\Expression(
     *     "this.getOpponent().getLeague() == this.getLeague()",
     *     message="Die Mannschaft befindet sich nicht in der Liga!"
     * )
     * @Assert\Expression(
     *     "this.getOpponent() != this.getTeam()",
     *     message="Bitte wählen Sie unterschiedliche Mannschaften!"
     * )
	 * @Input(groupBy="league")
     * @ORM\ManyToOne(targetEntity="App\Entity\Team")
     * @ORM\JoinColumn(nullable=false)
     */
    private $opponent;

    /**
	 * @Input
     * @ORM\Column(type="integer")
     */
    private $score;
	
    /**
	 * @Input
     * @ORM\Column(type="integer")
     */
    private $opponentScore;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLeague(): ?League
    {
        return $this->league;
    }

    public function setLeague(?League $league): self
    {
        $this->league = $league;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;

        return $this;
    }

    public function getOpponent(): ?Team
    {
        return $this->opponent;
    }

    public function setOpponent(?Team $opponent): self
    {
        $this->opponent = $opponent;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getOpponentScore(): ?int
    {
        return $this->opponentScore;
    }

    public function setOpponentScore(int $opponentScore): self
    {
        $this->opponentScore = $opponentScore;

        return $this;
    }
	public function __ToString() {
         		return $this->getTeam()->getName() . "  vs ". $this->getOpponent()->getName() . " (".$this->getScore().":".$this->getOpponentScore().")";
         	}

}
