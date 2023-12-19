<?php
namespace App\Resources;

use App\Entity\Product as ProductEntity;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductGetQueryResource
{
    public static function get(ProductEntity $product) {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
        ];
    }
}
