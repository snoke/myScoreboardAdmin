<?php
namespace App\Action\Command;

use App\Entity\Cart;
use App\Entity\Product as ProductEntity;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Resources\ProductGetQueryResource;
use Doctrine\ORM\EntityManagerInterface;

class ProductGetQuery
{
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;
    private ProductRepository $productRepository;

    public function __construct(EntityManagerInterface $entityManager, CartRepository $cartRepository, ProductRepository $productRepository) {
        $this->entityManager = $entityManager;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    protected function execute(array $parameters = [])
    {
        return array_map(function(ProductEntity $product) {
            return ProductGetQueryResource::get($product);
        }, $this->productRepository->findAll());
    }
}
