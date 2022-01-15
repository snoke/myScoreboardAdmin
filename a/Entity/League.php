<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Annotations\Input;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

class League extends Entity
{
	public function __ToString() {
		return $this->name;
	}
}
