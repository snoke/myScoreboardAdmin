<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Entity;
use App\Entity\Role;
use App\Entity\Team;
use App\Entity\League;
use App\Entity\Member;
use App\Entity\Party;

use Symfony\Component\Form\Extension\Core\Type\TextType;
class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{

	protected $appname = "myScoreboardAdmin";
	protected $entites;
	protected $em;
    
	protected function getSearchForm() {
        return $this->createFormBuilder()
            ->add('search', TextType::class,['label'=>false])
            ->getForm();
            
}
	public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
		$this->entities=new ArrayCollection([
			"team" => Team::class,
			"league" => League::class,
			"member" => Member::class,
			"party" => Party::class,
			"role" => Role::class
		]);
	}
}
