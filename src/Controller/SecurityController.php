<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Session\Session;

class SecurityController extends AbstractController
{

/**
* @Route("/makeuser" , methods={"GET"})
*/
public function mkuser(EntityManagerInterface $em,  UserPasswordHasherInterface  $encoder)
{
    $user = new User();
    $user->setEmail("test@test.de");
    $user->setName("Test");
    $user->addRole("ROLE_ADMIN");
    $user->addRole("ROLE_USER");
    $user->setPassword($encoder->hashPassword($user,"test"));
    $em->persist($user);
    $em->flush();
    die("done");
    
}

/**
 * @Route("/login.html", name="app_login")
 */
public function login(AuthenticationUtils $authenticationUtils,Session $session): Response
{

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
 * @Route("/logout.html", name="app_logout")
 */
public function app_logout(Session $session)
{
    throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
}
}
