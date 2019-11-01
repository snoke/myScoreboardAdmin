<?php
declare(strict_types=1);
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\RoleRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Entity;
use App\Entity\Role;
use App\Entity\League;
use App\Entity\Team;
use App\Entity\Member;
use App\Entity\Match;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use \Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Doctrine\Common\Annotations\AnnotationReader as DocReader;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Annotations\Input;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
class EntityController extends AbstractController
{
	private $appname = "myScoreboardAdmin";
	private $entites;
	private $em;
	public function __construct(EntityManagerInterface $em) {
		
		$this->em = $em;
		$this->entities=new ArrayCollection([
			"league" => League::class,
			"team" => Team::class,
			"member" => Member::class,
			"role" => Role::class,
			"match" => Match::class,
		]);
	}
	
	private function getSearchForm() {
			return $this->createFormBuilder()
				->add('search', TextType::class,['label'=>false])
				->getForm();
				
	}
	/**
	* @Route("/search.html",name="search_post", methods={"POST"})
	*/
	public function search(Request $request,Session $session) {
		$form = $this->getSearchForm();
		$form->handleRequest($request);
		$results = [];
		if ($form->isSubmitted()) {
			$search = strtolower($_POST["form"]["search"]);
			foreach($this->entities as $key => $class) {
				$repository = $this->em->getRepository($this->entities[$key]);
				foreach($repository->findAll() as $k=>$entity) {
					if (strpos(strtolower($entity->__ToString()), $search) !== FALSE) {
						$results[$key][] = $entity;
					}
				}
			}
		}
        return $this->render('search.html.twig',[
			'results' 	=> 	$results,
			'entities'			=>	$this->entities,
			'searchForm'	=>	$this->getSearchForm()->createView(),
			'appname'		=> $this->appname,
			'messages' 				=> ['success' => $session->getFlashBag()->get('success'),
							'warning' => $session->getFlashBag()->get('warning'),
							'error' => $session->getFlashBag()->get('error')
							]
		]);
	}	
	/**
	 * @Route("/scoreboard.html",name="index" )
	 * @Route("/index.html")
	 * @Route("/")
	*/
	public function index(Session $session) {
		
		$league = $this->em->getRepository($this->entities["league"])->findOneBy([]);
		if (null===$league) {
			$session->getFlashBag()->add('error', 'Please add a league!');
			return $this->redirectToRoute('add_entity',['entity'=>'league']);
		} else {
			return $this->redirectToRoute('scoreboard',['leagueId'=>$league->getId()]);
		}
	}	
	/**
	 * @Route("/scoreboard/{leagueId}.html",name="scoreboard")
	*/
	public function scoreboard(int $leagueId,Session $session) {
		$league = $this->em->getRepository($this->entities["league"])->findOneBy(['id'=>$leagueId]);
		if (null===$league) {
			$session->getFlashBag()->add('error', 'league not found!');
			return $this->redirectToRoute('scoreboard');
		}
		$teams = $this->em->getRepository($this->entities["team"])->findBy(['league' => $league]);
		usort($teams, function ($a,$b) 
		{
			return $a->getScore()===$b->getScore()?$a->getGoals() <= $b->getGoals():$a->getScore() <= $b->getScore();
		});
        return $this->render('scoreboard.html.twig',[
			'entities'		=>	$this->entities,
			'searchForm'	=>	$this->getSearchForm()->createView(),
			'leagues' 		=> $this->em->getRepository($this->entities["league"]),
			'league' 		=> $league,
			'matches' 		=> $this->em->getRepository($this->entities["match"]),
			"roles"			=> $this->em->getRepository($this->entities["role"]),
			"teams" 		=> $teams,
			"members" 		=> $this->em->getRepository($this->entities["member"]),
			'appname'		=> $this->appname,
			'messages' 				=> ['success' => $session->getFlashBag()->get('success'),
							'warning' => $session->getFlashBag()->get('warning'),
							'error' => $session->getFlashBag()->get('error')
							]
		]);
	}	
	/**
	* @IsGranted("ROLE_ADMIN")
	* @Route("/remove/{entity}/{id}.html",name="remove_entity", methods={"GET","POST"})
	*/
    public function remove(string $entity,int $id,Session $session)
    {
		$repository = $this->em->getRepository($this->entities[$entity]);
		$obj = $repository->findOneBy(["id"=>$id]);
		$this->em->remove($obj);
		$this->em->flush();
		$session->getFlashBag()->add('success', $entity. ' removed!');
		return $this->redirectToRoute('add_entity',['entity'=>$entity]);
    }
	/**
	* @IsGranted("ROLE_ADMIN")
	* @Route("add/{entity}.html",name="add_entity" , methods={"GET","POST"})
	* @Route("edit/{entity}/{id}.html",name="edit_entity", options={"expose"=true}, methods={"GET","POST"})
	*/
    public function add_entity(string $entity,?int $id=null,Request $request,Session $session)
    {
		$repository = $this->em->getRepository($this->entities[$entity]);
		
		$obj = $repository->findOneBy(['id' => $id]);
		$obj = $obj?$obj:new $this->entities[$entity]();
		$form = $this->createFormBuilder($obj);
		//echo '<pre>';
		//var_dump($choices);
		//die();
		foreach($this->getProperties($obj) as $property) {
			if ($property->name=="id") {
				$selectEntityForm = $this->createFormBuilder()
					->add($entity, ChoiceType::class, [
						'attr' => ['class'=>'entitySelector'],
						'disabled' => $property->disabled,
						'group_by' => $property->groupBy,
						'placeholder' => 'new',
						'choices' => $repository->findAll(),
						'data' => $id,  
						'choice_value' => function ($choice) use($repository,$entity) {
							return $choice==null ? "" : $repository->findOneBy(['id'=>$choice])->getId();
						},
						'choice_label' => function ($choice, $key, $value) use($repository,$entity) {
							return $choice==null ? "" : $repository->findOneBy(['id'=>$choice])->__ToString();
						}
				])->getForm();
			}
			elseif ($property->type=="string") {
				$form= $form->add($property->name, TextType::class, [
						'attr' => ['required' => true,'disabled'=>$property->disabled==true?true:false]
				]);
			} elseif ($property->type=="integer") {
				$form->add($property->name, IntegerType::class, [
						'attr' => ['required' => true,'disabled'=>$property->disabled==true?true:false]
				]);
			} elseif ($property->targetEntity!==null) {
				if ($this->em->getRepository($property->targetEntity)->findOneBy([])===null) {
					$entities = array_flip($this->entities->toArray());
					$session->getFlashBag()->add('error', 'please add a ' . $property->name);
					return $this->redirectToRoute('add_entity',['entity'=>$entities[$property->targetEntity]]);
				}
				$reflectionClass = new \ReflectionClass($property->targetEntity);
				$instance = $reflectionClass->newInstance();
				$groupBy=null;
				if (null!==$property->groupBy) {
					$groupBy = function($choice, $key, $value) use ($repository,$instance) {
						if ($choice!==null) {
							return $this->em->getRepository(get_class($instance))->findOneBy(['id'=>$value])->getLeague();
						}
					};
				}
				$form->add($property->name, EntityType::class, [
					'class' => $property->targetEntity,
					'attr' => ['required' => true],
					'group_by' => $groupBy,
					'choice_attr' => function($key, $val, $index) use($property) {
						return $property->disabled ? ['disabled' => 'disabled'] : [];
					},
				]);
			}
		}
			$form=$form->getForm();
			
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {
			$this->em->persist($obj);
			$this->em->flush();
			$session->getFlashBag()->add('success', $entity. ' saved!');
			return $this->redirectToRoute('add_entity',['entity'=>$entity]);
		}
					
        return $this->render('entity.html.twig',[
			'selectEntityForm' 	=> 	$selectEntityForm->createView(),
			'form'				=>	$form->createView(),
			'entities'			=>	$this->entities,
			'entity'			=>	$entity,
			'id'			=>	$id,
			'searchForm'	=>	$this->getSearchForm()->createView(),
			'appname'		=> $this->appname,
			'messages' 				=> ['success' => $session->getFlashBag()->get('success'),
							'warning' => $session->getFlashBag()->get('warning'),
							'error' => $session->getFlashBag()->get('error')
							]
		]);
    }
	private function getProperties($entity) {
		
		$reflectionExtractor = new ReflectionExtractor();
		//$doctrineExtractor = new DoctrineExtractor($this->em); 
		$phpDocExtractor = new PhpDocExtractor();
		$propertyInfo = new PropertyInfoExtractor(
			// List extractors
			[
				//$phpDocExtractor,
				$reflectionExtractor,
				//$doctrineExtractor,
			],
			// Type extractors
			[
				//$reflectionExtractor,
				$phpDocExtractor,
				//$doctrineExtractor,
			]
		);
		
		$class=get_class($entity);
		$reflector = new \ReflectionClass($class);
		$docReader = new DocReader();
		$docReader->getClassAnnotations($reflector);
		$_annotations=[];
		
		foreach($propertyInfo->getProperties($class) as $property) {
			try {
			$reflector = new \ReflectionProperty($class, $property);
			} catch(\ReflectionException $e) {
				continue;
			}
			$annotations=["name" => $property ,"groupBy"=>null,"targetEntity"=>null,"type"=>null,"input"=>false];
			foreach($docReader->getPropertyAnnotations($reflector,new \ReflectionProperty($class, $property)) as $annotation) {
				if ($annotation instanceof \Doctrine\ORM\Mapping\ManyToOne) {
					if (isset($annotation->targetEntity)) {
						$annotations["type"] = "ManyToOne";
						$annotations["targetEntity"] = $annotation->targetEntity;
					}
				} elseif ($annotation instanceof \Doctrine\ORM\Mapping\Column) {
						$annotations["type"] = $annotation->type;
				} elseif ($annotation instanceof Input) {
						$annotations["input"] = true;
						$annotations["groupBy"] = $annotation->groupBy;
						$annotations["disabled"] = $annotation->disabled;
				}
			}
			(true===$annotations["input"])?$_annotations[] = (object)$annotations:null;
			
		}
		return $_annotations;
	}
	
	/**
	* @Route("/makeuser" , methods={"GET"})
	*/
    public function mkuser(EntityManagerInterface $em,UserPasswordEncoderInterface $encoder)
	{
		$user = new User();
		$user->setEmail("admin@appstack.de");
		$user->setName("Admin");
		$user->addRole("ROLE_ADMIN");
		$user->addRole("ROLE_USER");
		$user->setPassword($encoder->encodePassword($user,"admin"));
		$em->persist($user);
		$em->flush();
		die("done");
		
	}
	
    /**
     * @Route("/login.html", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils,Session $session): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error,
		
			'entities'			=>	$this->entities,
			'searchForm'	=>	$this->getSearchForm()->createView(),
			'appname'		=> $this->appname,
			'messages' 				=> ['success' => $session->getFlashBag()->get('success'),
							'warning' => $session->getFlashBag()->get('warning'),
							'error' => $session->getFlashBag()->get('error')
							]
							]);
    }

    /**
     * @Route("/app_logout", name="logout")
     */
    public function logout(Session $session)
    {
			$session->getFlashBag()->add('success',' logged out!');
			return $this->redirectToRoute('index');
    }
    /**
     * @Route("/logout.html", name="app_logout")
     */
    public function app_logout(Session $session)
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}