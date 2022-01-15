<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Annotations\Input;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\LeagueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeagueRepository::class)]
class League extends Entity
{
    /**
	 * @Input
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }
    /**
	 * @Input
     * @ORM\Column(type="string", length=255)
     */
    #[ORM\Column(type: 'string')]
    private $name;

    /**
     * @Assert\Expression(
     *     "this.getMaximumTeams()%2==0"
     * )
     * @Assert\Expression(
     *     "this.getMaximumTeams() > 2"
     * )
	 * @Input
     * @ORM\Column(type="integer")
     */
    #[ORM\Column(type: 'integer')]
    private $maximumTeams;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Team", mappedBy="league", orphanRemoval=true)
	 * @Input(groupBy="league")
     */
    #[ORM\OneToMany(targetEntity:"App\Entity\Team", mappedBy:"league", orphanRemoval:true)]
    private $teams;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Party", mappedBy="league", orphanRemoval=true)
     */
    #[ORM\OneToMany(targetEntity:"App\Entity\Party", mappedBy:"league", orphanRemoval:true)]
    private $parties;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->parties = new ArrayCollection();
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

    public function getMaximumTeams(): ?int
    {
        return $this->maximumTeams;
    }

    public function setMaximumTeams(int $maximumTeams): self
    {
        $this->maximumTeams = $maximumTeams;

        return $this;
    }

    /**
     * @return Collection|Team[]
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): self
    {
        if (!$this->teams->contains($team)) {
            $this->teams[] = $team;
            $team->setLeague($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): self
    {
        if ($this->teams->contains($team)) {
            $this->teams->removeElement($team);
            // set the owning side to null (unless already changed)
            if ($team->getLeague() === $this) {
                $team->setLeague(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Party[]
     */
    public function getParties(): Collection
    {
        return $this->parties;
    }

    public function addParty(Party $match): self
    {
        if (!$this->parties->contains($match)) {
            $this->parties[] = $match;
            $match->setLeague($this);
        }

        return $this;
    }

    public function removeParty(Party $match): self
    {
        if ($this->parties->contains($match)) {
            $this->parties->removeElement($match);
            if ($match->getLeague() === $this) {
                $match->setLeague(null);
            }
        }

        return $this;
    }
	public function __ToString() {
		return $this->name;
	}
	public function isFull() :bool 
	{
			return count($this->getTeams())>=$this->getMaximumTeams();
	}
	public function getGameDay() {
		$gameDay = null;
		foreach($this->getTeams() as $team) {
			$partiesAmount = count($team->getParties());
			if (null===$gameDay || $gameDay>$partiesAmount) {
				$gameDay = $partiesAmount;
			}
		}
		return ($gameDay===null)?1:$gameDay+1;
	}
    /**
     * @return bool
     */
    public function hasTeam(Team $team): bool
    {
		return in_array($team,$this->getTeams()->ToArray());
    }
}