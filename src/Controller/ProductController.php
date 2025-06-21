<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final class ProductController extends AbstractController
{
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des produits',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class))
        )
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'La page demandée',
        in: 'query',
        schema: new OA\Schema(type: 'integer',)
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Le nombre d\'éléments demandé',
        in: 'query',
        schema: new OA\Schema(type: 'integer',)
    )]
    #[OA\Tag(name: 'Produits')]
    #[Route('/api/products', name: 'app_product', methods: ['GET'])]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllProducts-" . $page . "-" . $limit;

        $jsonProductList = $cachePool->get($idCache, function(ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
            $item->tag('productsCache');
            $productList = $productRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($productList, 'json');
        });

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[OA\Response(
        response: 200,
        description: 'Retourne les détails d\'un produit',
        content: new OA\JsonContent(
            ref: new Model(type: Product::class)
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID du produit',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'Produits')]
    #[Route('/api/products/{id}', name: 'app_detail_product', methods: ['GET'])]
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse($jsonProduct, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
