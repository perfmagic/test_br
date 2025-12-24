<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?EntityManagerInterface $entityManager;
    private $rootUser;
    private $normalUser;
    private $rootToken;
    private $normalUserToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $jwtManager = $container->get(JWTTokenManagerInterface::class);

        // Start transaction
        $this->entityManager->beginTransaction();

        // Create root user
        $this->rootUser = new User();
        $this->rootUser->setLogin('root');
        $this->rootUser->setPhone('12345678');
        $this->rootUser->setRoles(['ROLE_ROOT']);
        $this->rootUser->setPassword($passwordHasher->hashPassword($this->rootUser, 'password'));
        $this->entityManager->persist($this->rootUser);

        // Create normal user
        $this->normalUser = new User();
        $this->normalUser->setLogin('user');
        $this->normalUser->setPhone('87654321');
        $this->normalUser->setRoles(['ROLE_USER']);
        $this->normalUser->setPassword($passwordHasher->hashPassword($this->normalUser, 'password'));
        $this->entityManager->persist($this->normalUser);

        $this->entityManager->flush();

        // Generate tokens
        $this->rootToken = $jwtManager->create($this->rootUser);
        $this->normalUserToken = $jwtManager->create($this->normalUser);
    }

    public function testCreateUser()
    {
        // Test successful creation
        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->rootToken],
            json_encode(['login' => 'newuser', 'phone' => '11112222', 'password' => 'newpass'])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('newuser', $response['login']);
    }

    public function testCreateUserValidation()
    {
        // Test validation failure (login too long)
        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->rootToken],
            json_encode(['login' => 'thisloginistoolong', 'phone' => '11112222', 'password' => 'newpass'])
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetUserAsRoot()
    {
        $this->client->request('GET', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->rootToken]);
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($this->normalUser->getLogin(), $response['login']);
    }

    public function testGetUserAsNormalUserOwnProfile()
    {
        $this->client->request('GET', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->normalUserToken]);
        $this->assertResponseIsSuccessful();
    }

    public function testGetUserAsNormalUserOtherProfile()
    {
        // Prevents enumeration attack
        $this->client->request('GET', '/v1/api/users/' . $this->rootUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->normalUserToken]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentUser()
    {
        $this->client->request('GET', '/v1/api/users/999999', [], [], ['HTTP_Authorization' => 'Bearer ' . $this->rootToken]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateUserAsRoot()
    {
        $this->client->request(
            'PUT',
            '/v1/api/users/' . $this->normalUser->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->rootToken],
            json_encode(['login' => 'updated', 'phone' => '88887777', 'password' => 'updated'])
        );
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('updated', $response['login']);
    }

    public function testUpdateUserAsNormalUserOtherProfile()
    {
        $this->client->request(
            'PUT',
            '/v1/api/users/' . $this->rootUser->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->normalUserToken],
            json_encode(['login' => 'hacker', 'phone' => 'hacker', 'password' => 'hacker'])
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteUserAsRoot()
    {
        $this->client->request('DELETE', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->rootToken]);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteUserAsNormalUser()
    {
        $this->client->request('DELETE', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->normalUserToken]);
        $this->assertResponseStatusCodeSame(404); // Not found because they don't have permission
    }

    public function testUnauthenticatedAccess()
    {
        $this->client->request('GET', '/v1/api/users/' . $this->normalUser->getId());
        $this->assertResponseStatusCodeSame(401);

        $this->client->request('POST', '/v1/api/users');
        $this->assertResponseStatusCodeSame(401);

        $this->client->request('PUT', '/v1/api/users/' . $this->normalUser->getId());
        $this->assertResponseStatusCodeSame(401);

        $this->client->request('DELETE', '/v1/api/users/' . $this->normalUser->getId());
        $this->assertResponseStatusCodeSame(401);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Rollback the transaction
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
