<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Annotations\Input;
use App\Repository\RoleRepository;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RoleRepository")
 */
#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role extends Entity
{
    /**
	 * @Input
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
     #[ORM\Id()]
     #[ORM\GeneratedValue()]
     #[ORM\Column(type:"integer")]
    private $id;

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
		return $this->name;
	}
	public function setId(?int $id) {
		$this->id = $id;
		
		return $this;
	}
}
