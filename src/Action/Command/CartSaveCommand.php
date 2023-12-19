<?php
namespace App\Action\Command;

use App\Entity\Cart;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartSaveCommand
{
    private EntityManagerInterface $entityManager;
    private CartRepository $cartRepository;
    private ProductRepository $productRepository;
    private int $cartId;
    private int $productId;

    public function __construct(EntityManagerInterface $entityManager, CartRepository $cartRepository, ProductRepository $productRepository) {
        $this->entityManager = $entityManager;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    protected function execute(Cart $cart, array $products): int
    {
        foreach($products as $product) {
            $cart->addProduct($product);
        }

        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }
}
