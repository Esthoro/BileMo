<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;

final class ClientController extends AbstractController
{
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des clients',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Client::class, groups: ['getClients']))
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
    #[OA\Tag(name: 'Clients')]
    #[Route('api/clients', name: 'app_client', methods: ['GET'])]
    public function getAllClients(ClientRepository $clientRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllClients-" . $page . "-" . $limit;

        $jsonClientList = $cachePool->get($idCache, function(ItemInterface $item) use ($clientRepository, $page, $limit, $serializer) {
            $item->tag('clientsCache');
            $clientList = $clientRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getClients']);
            return $serializer->serialize($clientList, 'json', $context);
        });

        return new JsonResponse($jsonClientList, Response::HTTP_OK, [], true);
    }

    #[OA\Response(
        response: 200,
        description: 'Retourne les détails d\'un client',
        content: new OA\JsonContent(
            ref: new Model(type: Client::class, groups: ['getClients'])
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID du client',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'Clients')]
    #[Route('/api/clients/{id}', name: 'app_detail_client', methods: ['GET'])]
    public function getDetailClient(Client $client, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getClients']);
        $jsonClient = $serializer->serialize($client, 'json', $context);
        return new JsonResponse($jsonClient, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
