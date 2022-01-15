<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\HttpFoundation\Request;

class ScoreboardController extends AbstractController
{
    /**
    * @IsGranted("ROLE_ADMIN")
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
    * @IsGranted("ROLE_ADMIN")
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
    * @IsGranted("ROLE_ADMIN")
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
            'parties' 		=> $this->em->getRepository($this->entities["party"]),
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
}
