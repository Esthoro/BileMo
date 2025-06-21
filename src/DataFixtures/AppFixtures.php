<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $clientPasswordHasher;

    public function __construct(UserPasswordHasherInterface $clientPasswordHasher) {
        $this->clientPasswordHasher = $clientPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        //Clients
        $listClient = [];
        for ($i = 1; $i <= 10; $i++) {
            $client = new Client();
            $client->setName('Client ' . $i);
            $client->setEmail('exemple' . $i . '@gmail.com');
            $client->setPassword($this->clientPasswordHasher->hashPassword($client, 'exemple' . $i));
            $client->setCreatedAt(new \DateTimeImmutable());
            $client->setRoles(["ROLE_CLIENT"]);
            $manager->persist($client);
            $listClient[] = $client;
        }

        //Users
        for ($i = 1; $i <= 20; $i++) {
            $user = new User();
            $user->setName('User ' . $i);
            $user->setEmail('exemple' . $i . '@gmail.com');
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setClient($listClient[array_rand($listClient)]);
            $manager->persist($user);
        }


        // Produits
        for ($i = 1; $i <= 20; $i++) {
            $product = new Product;
            $product->setName('Mobile de série ' . $i);
            $product->setDescription('Super mobile de série ' . $i);
            $product->setPrice('15.99');
            $product->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($product);
        }

        $manager->flush();
    }
}
