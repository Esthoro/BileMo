<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final class UserController extends AbstractController
{
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des utilisateurs',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['getUsers']))
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
    #[OA\Tag(name: 'Users')]
    #[Route('/api/users', name: 'app_user', methods: ['GET'])]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer, Request $request, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllUsers-" . $page . "-" . $limit;

        $jsonUserList = $cachePool->get($idCache, function(ItemInterface $item) use ($userRepository, $page, $limit, $serializer) {
            $item->tag('usersCache');
            $userList = $userRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['getUsers']);
            return $serializer->serialize($userList, 'json', $context);
        });


        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[OA\Response(
        response: 200,
        description: 'Retourne les détails d\'un utilisateur',
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ['getUsers'])
        )
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'utilisateur',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'Users')]
    #[Route('/api/users/{id}', name: 'app_detail_user', methods: ['GET'])]
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[OA\Response(
        response: 204,
        description: 'Utilisateur supprimé avec succès'
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'ID de l\'utilisateur à supprimer',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'Users')]
    #[Route('/api/users/{id}', name: 'app_delete_user', methods: ['DELETE'])]
    public function deleteUser(User $user, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): JsonResponse
    {
        $cachePool->invalidateTags(['usersCache']);
        $em->remove($user);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'hello'),
                new OA\Property(property: 'email', type: 'string', example: 'test@example.com'),
                new OA\Property(property: 'password', type: 'string', example: 'MotDePasse123!'),
                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-06-30T12:00:00+00:00'),
                new OA\Property(property: 'idClient', type: 'integer', example: 30),
            ],
            type: 'object'
        )
    )]

    #[OA\Response(
        response: 201,
        description: 'Utilisateur créé',
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ['getUsers'])
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur de validation',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(type: 'string')
        )
    )]
    #[OA\Tag(name: 'Users')]
    #[Route('/api/users', name: 'app_create_user', methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator,
    ClientRepository $clientRepository, ValidatorInterface $validator, TagAwareCacheInterface $cachePool): JsonResponse
    {

        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        // Récupération de l'ensemble des données envoyées sous forme de tableau
        $content = $request->toArray();

        // Récupération de l'idAuthor. S'il n'est pas défini, alors on met -1 par défaut.
        $idClient = $content['idClient'] ?? -1;

        // On cherche le client qui correspond et on l'assigne au user.
        // Si "find" ne trouve pas le client, alors null sera retourné.
        $user->setClient($clientRepository->find($idClient));

        if ($user->getCreatedAt() === null) {
            $user->setCreatedAt(new \DateTimeImmutable());
        }

        //On vérifie les erreurs
        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUsers']);

        $jsonUser = $serializer->serialize($user, 'json', $context);

        $location = $urlGenerator->generate('app_detail_user', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $cachePool->invalidateTags(['usersCache']);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }
}
