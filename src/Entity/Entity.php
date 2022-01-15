<?php

namespace App\Entity;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Doctrine\Common\Annotations\AnnotationReader as DocReader;

abstract class Entity
{
	abstract public function __ToString();
	 
	public static function getShortNameLowered() {
		$function = new \ReflectionClass(get_called_class());
		return strtolower($function->getShortName());
	}
	
	public static function getShortName() {
		$function = new \ReflectionClass(get_called_class());
		return $function->getShortName();
	}
	public function getProperties() {
		
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
		$entity=$this;
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