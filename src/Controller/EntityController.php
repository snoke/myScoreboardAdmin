<?php

namespace App\Controller;


use App\Annotations\Input;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Doctrine\Common\Annotations\AnnotationReader as DocReader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
class EntityController extends AbstractController
{


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
        foreach($this->getProperties($obj) as $property) {
            
            if ($property->name=="id") {
                $selectEntityForm = $this->createFormBuilder()
                    ->add($entity, ChoiceType::class, [
                        'attr' => ['class'=>'entitySelector'],
                        'disabled' => $property->disabled or false,
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
        $phpDocExtractor = new PhpDocExtractor();
        $propertyInfo = new PropertyInfoExtractor(
            [
                $reflectionExtractor,
            ],
            [
                $phpDocExtractor,
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

}
