<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Annotations\Input;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LeagueRepository")
 */
class League extends Entity
{
    /**
	 * @Input
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
	 * @Input
     * @ORM\Column(type="string", length=255)
     */
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
    private $maximumTeams;

    /**
	 * @Input(groupBy="league")
     * @ORM\OneToMany(targetEntity="App\Entity\Team", mappedBy="league", orphanRemoval=true)
     */
    private $teams;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Match", mappedBy="league", orphanRemoval=true)
     */
    private $matches;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->matches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection|Match[]
     */
    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(Match $match): self
    {
        if (!$this->matches->contains($match)) {
            $this->matches[] = $match;
            $match->setLeague($this);
        }

        return $this;
    }

    public function removeMatch(Match $match): self
    {
        if ($this->matches->contains($match)) {
            $this->matches->removeElement($match);
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
			$matchesAmount = count($team->getMatches());
			if (null===$gameDay || $gameDay>$matchesAmount) {
				$gameDay = $matchesAmount;
			}
		}
		$return ($gameDay===null)?1:$gameDay+1;
	}
    /**
     * @return bool
     */
    public function hasTeam(Team $team): bool
    {
		return in_array($team,$this->getTeams()->ToArray());
    }
}
