<?php
namespace App\Annotations;
/**
 * @Annotation
 * @Target({"METHOD","PROPERTY","CLASS"})
 */
class Input
{
	/** @var string */
    public $groupBy;
    public $disabled;
    public $hidden;
    // some code
}