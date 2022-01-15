<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use App\Annotations\Input;
use App\Repository\MemberRepository;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MemberRepository")
 */
 #[ORM\Entity(repositoryClass: MemberRepository::class)]
class Member extends Entity
{
    /**
	 * @Input(groupBy="team")
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type:"integer")]
    private $id;

    /**
	 * @Input(groupBy="league")
     * @ORM\ManyToOne(targetEntity="App\Entity\Team", inversedBy="members")
     * @ORM\JoinColumn(nullable=false)
     */
    #[ORM\ManyToOne(targetEntity:"App\Entity\Team", inversedBy:"members")]
    #[ORM\JoinColumn(nullable:false)]
    private $team;


    /**
	 * @Input
     * @ORM\ManyToOne(targetEntity="App\Entity\Role")
     * @ORM\JoinColumn(nullable=false)
     */
 #[ORM\ManyToOne(targetEntity:"App\Entity\Role")]
     #[ORM\JoinColumn(nullable:false)]
    private $role;
	
    /**
	 * @Input
     * @ORM\Column(type="string", length=255)
     */
 #[ORM\Column(type:"string")]
    private $name;

    public function getId(): ?int
    {
        return $this->id;
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


    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;

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
	
	public function __ToString() {
		return $this->getName();
		}
}
