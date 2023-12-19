<?php
namespace App\Action\Command;

use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartRemoveProductCommand
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

    protected function execute(int $cartId, int $productId): int
    {
        $this->cartId = $cartId;
        $this->productId = $productId;

        $cart = $this->cartRepository->find($cartId);
        $product = $this->productRepository->find($productId);

        $cart->addProduct($product);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();
    }
}
