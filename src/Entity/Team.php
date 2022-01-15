<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Annotations\Input;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\TeamRepository;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TeamRepository")
 */
#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team extends Entity
{
    /**
	 * @Input(groupBy="league")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    /**
	 * @Input
     * @Assert\Expression(
     *     "this.getLeague().hasTeam(this) || this.getLeague().getParties().count() == 0",
     *     message="Die Liga läuft bereits!"
     * )
     * @Assert\Expression(
     *     "this.getLeague().hasTeam(this) || this.getLeague().getMaximumTeams() > this.getLeague().getTeams().count()",
     *     message="Die Liga ist bereits voll!"
     * )
     * @ORM\ManyToOne(targetEntity="App\Entity\League", inversedBy="teams")
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\ManyToOne(targetEntity:'App\Entity\League', inversedBy:'teams')]
    #[ORM\JoinColumn(nullable:false)]
    private $league;

    /**
	 * @Input
     * @ORM\Column(type="string", length=255)
     */
    #[ORM\Column(type:"string")]
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Member", mappedBy="team", orphanRemoval=true)
     */
    #[ORM\OneToMany(targetEntity:"App\Entity\Member", mappedBy:"team", orphanRemoval:true)]
    private $members;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Party", mappedBy="team", cascade={"persist","remove"}, orphanRemoval=true)
     */
    #[ORM\OneToMany(targetEntity:"App\Entity\Party", mappedBy:"team", cascade:["persist","remove"], orphanRemoval:true)]
    private $homeParties;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Party", mappedBy="opponent", cascade={"persist","remove"}, orphanRemoval=true)
     */
    #[ORM\OneToMany(targetEntity:"App\Entity\Party", mappedBy:"opponent", cascade:["persist","remove"], orphanRemoval:true)]
    private $guestParties;
	
	
    public function __construct()
    {
        $this->homeParties = new ArrayCollection();
        $this->guestParties = new ArrayCollection();
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
     * @return Collection|Party[]
     */
    public function getHomeParties(): Collection
    {
        return $this->homeParties;
    }

    public function addHomeParty(Party $homeParty): self
    {
        if (!$this->homeParties->contains($homeParty)) {
            $this->homeParties[] = $homeParty;
            $homeParty->setTeam($this);
        }

        return $this;
    }

    public function removeHomeParty(Party $homeParty): self
    {
        if ($this->homeParties->contains($homeParty)) {
            $this->homeParties->removeElement($homeParty);
            // set the owning side to null (unless already changed)
            if ($homeParty->getTeam() === $this) {
                $homeParty->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Party[]
     */
    public function getGuestParties(): Collection
    {
        return $this->guestParties;
    }
	
    public function addGuestParty(Party $guestParty): self
    {
        if (!$this->guestParties->contains($guestParty)) {
            $this->guestParties[] = $guestParty;
            $guestParty->setOpponent($this);
        }

        return $this;
    }

    public function removeGuestParty(Party $guestParty): self
    {
        if ($this->guestParties->contains($guestParty)) {
            $this->guestParties->removeElement($guestParty);
            if ($guestParty->getOpponent() === $this) {
                $guestParty->setOpponent(null);
            }
        }
        return $this;
    }
	
	public function getParties() {
		$parties = [];
		foreach($this->getHomeParties() as $match) {
			$parties[$match->getId()] = $match;
		}
		foreach($this->getGuestParties() as $match) {
			$parties[$match->getId()] = $match;
		}
		return new ArrayCollection(array_values($parties));
	}
	
	public function getWins() {
		$parties = new ArrayCollection();
		foreach($this->getHomeParties() as $match) {
			if ($match->getScore()>$match->getOpponentScore()) {
				$parties->add($match);
			}
		}
		foreach($this->getGuestParties() as $match) {
			if ($match->getScore()<$match->getOpponentScore()) {
				$parties->add($match);
			}
		}
		return $parties;
	}
	public function getDraws() {
		$parties = new ArrayCollection();
		foreach($this->getParties() as $match) {
			if ($match->getScore()==$match->getOpponentScore()) {
				$parties->add($match);
			}
		}
		return $parties;
	}
	public function getLosses() {
		$parties = new ArrayCollection();
		foreach($this->getHomeParties() as $match) {
			if ($match->getScore()<$match->getOpponentScore()) {
				$parties->add($match);
			}
		}
		foreach($this->getGuestParties() as $match) {
			if ($match->getScore()>$match->getOpponentScore()) {
				$parties->add($match);
			}
		}
		return $parties;
	}
	public function getGoals() {
		$goals = 0;
		foreach($this->getHomeParties() as $match) {
			$goals += $match->getScore();
		}
		foreach($this->getGuestParties() as $match) {
			$goals += $match->getOpponentScore();
		}
		return $goals;
	}
	public function getOpponentGoals() {
		$goals = 0;
		foreach($this->getHomeParties() as $match) {
			$goals += $match->getOpponentScore();
		}
		foreach($this->getGuestParties() as $match) {
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
			if (count($team->getParties())<count($this->getParties())) {
				return false;
			}
		}
		return true;
	}
	public function __ToString() {
		return $this->getName();
		}
}
