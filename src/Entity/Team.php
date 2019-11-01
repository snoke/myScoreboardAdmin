<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Annotations\Input;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TeamRepository")
 */
class Team extends Entity
{
    /**
	 * @Input(groupBy="league")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\Expression(
     *     "this.getLeague().hasTeam(this) || this.getLeague().getMatches().count() == 0",
     *     message="Die Liga läuft bereits!"
     * )
     * @Assert\Expression(
     *     "this.getLeague().hasTeam(this) || this.getLeague().getMaximumTeams() > this.getLeague().getTeams().count()",
     *     message="Die Liga ist bereits voll!"
     * )
	 * @Input
     * @ORM\ManyToOne(targetEntity="App\Entity\League", inversedBy="teams")
     * @ORM\JoinColumn(nullable=false)
     */
    private $league;

    /**
	 * @Input
	 * @ORM\ManyToOne(targetEntity="App\Entity\League",cascade={"persist"})
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Member", mappedBy="team", orphanRemoval=true)
     */
    private $members;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Match", mappedBy="team", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $homeMatches;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Match", mappedBy="opponent", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $guestMatches;
	
	
    public function __construct()
    {
        $this->homeMatches = new ArrayCollection();
        $this->guestMatches = new ArrayCollection();
        $this->members = new ArrayCollection();
    }

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Member[]
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Member $member): self
    {
        if (!$this->members->contains($member)) {
            $this->members[] = $member;
            $member->setTeam($this);
        }

        return $this;
    }

    public function removeMember(Member $member): self
    {
        if ($this->members->contains($member)) {
            $this->members->removeElement($member);
            // set the owning side to null (unless already changed)
            if ($member->getTeam() === $this) {
                $member->setTeam(null);
            }
        }

        return $this;
    }
	
    /**
     * @return Collection|Match[]
     */
    public function getHomeMatches(): Collection
    {
        return $this->homeMatches;
    }

    public function addHomeMatch(Match $homeMatch): self
    {
        if (!$this->homeMatches->contains($homeMatch)) {
            $this->homeMatches[] = $homeMatch;
            $homeMatch->setTeam($this);
        }

        return $this;
    }

    public function removeHomeMatch(Match $homeMatch): self
    {
        if ($this->homeMatches->contains($homeMatch)) {
            $this->homeMatches->removeElement($homeMatch);
            // set the owning side to null (unless already changed)
            if ($homeMatch->getTeam() === $this) {
                $homeMatch->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Match[]
     */
    public function getGuestMatches(): Collection
    {
        return $this->guestMatches;
    }
	
    public function addGuestMatch(Match $guestMatch): self
    {
        if (!$this->guestMatches->contains($guestMatch)) {
            $this->guestMatches[] = $guestMatch;
            $guestMatch->setOpponent($this);
        }

        return $this;
    }

    public function removeGuestMatch(Match $guestMatch): self
    {
        if ($this->guestMatches->contains($guestMatch)) {
            $this->guestMatches->removeElement($guestMatch);
            if ($guestMatch->getOpponent() === $this) {
                $guestMatch->setOpponent(null);
            }
        }
        return $this;
    }
	
	public function getMatches() {
		$matches = [];
		foreach($this->getHomeMatches() as $match) {
			$matches[$match->getId()] = $match;
		}
		foreach($this->getGuestMatches() as $match) {
			$matches[$match->getId()] = $match;
		}
		return new ArrayCollection(array_values($matches));
	}
	
	public function getWins() {
		$matches = new ArrayCollection();
		foreach($this->getHomeMatches() as $match) {
			if ($match->getScore()>$match->getOpponentScore()) {
				$matches->add($match);
			}
		}
		foreach($this->getGuestMatches() as $match) {
			if ($match->getScore()<$match->getOpponentScore()) {
				$matches->add($match);
			}
		}
		return $matches;
	}
	public function getDraws() {
		$matches = new ArrayCollection();
		foreach($this->getMatches() as $match) {
			if ($match->getScore()==$match->getOpponentScore()) {
				$matches->add($match);
			}
		}
		return $matches;
	}
	public function getLosses() {
		$matches = new ArrayCollection();
		foreach($this->getHomeMatches() as $match) {
			if ($match->getScore()<$match->getOpponentScore()) {
				$matches->add($match);
			}
		}
		foreach($this->getGuestMatches() as $match) {
			if ($match->getScore()>$match->getOpponentScore()) {
				$matches->add($match);
			}
		}
		return $matches;
	}
	public function getGoals() {
		$goals = 0;
		foreach($this->getHomeMatches() as $match) {
			$goals += $match->getScore();
		}
		foreach($this->getGuestMatches() as $match) {
			$goals += $match->getOpponentScore();
		}
		return $goals;
	}
	public function getOpponentGoals() {
		$goals = 0;
		foreach($this->getHomeMatches() as $match) {
			$goals += $match->getOpponentScore();
		}
		foreach($this->getGuestMatches() as $match) {
			$goals += $match->getScore();
		}
		return $goals;
	}
	public function getScore() {
		$score = count($this->getWins()) * 3;
		$score += count($this->getDraws());
		return $score;
	}
	public function isMyTurn() {
		foreach($this->league->getTeams() as $team) {
			if (count($team->getMatches())<count($this->getMatches())) {
				return false;
			}
		}
		return true;
	}
	public function __ToString() {
		return $this->getName();
		}
}
