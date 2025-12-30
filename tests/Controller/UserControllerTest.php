<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ?EntityManagerInterface $entityManager;
    private User $rootUser;
    private User $normalUser;
    private string $rootToken;
    private string $normalUserToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $jwtManager = $container->get(JWTTokenManagerInterface::class);

        $this->entityManager->beginTransaction();

        $this->rootUser = new User();
        $this->rootUser->setLogin('root');
        $this->rootUser->setPhone('12345678');
        $this->rootUser->setRoles(['ROLE_ROOT']);
        $this->rootUser->setPassword($passwordHasher->hashPassword($this->rootUser, 'password'));
        $this->entityManager->persist($this->rootUser);

        $this->normalUser = new User();
        $this->normalUser->setLogin('user');
        $this->normalUser->setPhone('87654321');
        $this->normalUser->setRoles(['ROLE_USER']);
        $this->normalUser->setPassword($passwordHasher->hashPassword($this->normalUser, 'password'));
        $this->entityManager->persist($this->normalUser);

        $this->entityManager->flush();

        $this->rootToken = $jwtManager->create($this->rootUser);
        $this->normalUserToken = $jwtManager->create($this->normalUser);
    }

    public function testPostAsNormalUserCreatesUserSuccessfully(): void
    {
        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->normalUserToken],
            json_encode(['login' => 'newuser', 'phone' => '11112222', 'password' => 'newpass'])
        );

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('login', $response);
        $this->assertArrayHasKey('phone', $response);
        // Requirement: password hash must be in response
        $this->assertArrayHasKey('password', $response);
        $this->assertEquals('newuser', $response['login']);
        $this->assertEquals('11112222', $response['phone']);
    }

    #[DataProvider('provideInvalidPostData')]
    public function testPostWithInvalidDataReturns422(array $payload, string $expectedErrorField): void
    {
        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->rootToken],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(422);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('detail', $response);
    }

    public static function provideInvalidPostData(): \Generator
    {
        yield 'Login too long' => [['login' => 'thisiswaytoolong', 'phone' => '12345678', 'password' => 'secret'], 'login'];
        yield 'Phone too long' => [['login' => 'valid', 'phone' => '123456789', 'password' => 'secret'], 'phone'];
        yield 'Password too long' => [['login' => 'valid', 'phone' => '12345678', 'password' => 'thisiswaytoolong'], 'password'];
        yield 'Login missing' => [['phone' => '12345678', 'password' => 'secret'], 'login'];
        yield 'Phone missing' => [['login' => 'valid', 'password' => 'secret'], 'phone'];
        yield 'Password missing' => [['login' => 'valid', 'phone' => '12345678'], 'password'];
    }

    public function testGetAsRootReturnsCorrectData(): void
    {
        $this->client->request('GET', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->rootToken]);
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('login', $response);
        $this->assertArrayHasKey('phone', $response);
        $this->assertArrayHasKey('password', $response); // Requirement: password hash must be in response
        $this->assertEquals($this->normalUser->getLogin(), $response['login']);
    }

    public function testGetAsNormalUserOwnProfileIsSuccessful(): void
    {
        $this->client->request('GET', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->normalUserToken]);
        $this->assertResponseIsSuccessful();
    }

    public function testGetAsNormalUserOtherProfileReturns404(): void
    {
        $this->client->request('GET', '/v1/api/users/' . $this->rootUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->normalUserToken]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetNonExistentUserReturns404(): void
    {
        $this->client->request('GET', '/v1/api/users/999999', [], [], ['HTTP_Authorization' => 'Bearer ' . $this->rootToken]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPutAsNormalUserOwnProfileIsSuccessful(): void
    {
        $this->client->request(
            'PUT',
            '/v1/api/users/' . $this->normalUser->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->normalUserToken],
            json_encode(['login' => 'updated', 'phone' => '88887777', 'password' => 'newpass'])
        );
        $this->assertResponseIsSuccessful();
    }

    public function testPutReturnsOnlyId(): void
    {
        $this->client->request(
            'PUT',
            '/v1/api/users/' . $this->normalUser->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->rootToken],
            json_encode(['login' => 'updated', 'phone' => '88887777', 'password' => 'newpass'])
        );
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertCount(1, $response, 'Response should only contain the id');
    }

    public function testPutAsNormalUserOtherProfileReturns404(): void
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

    public function testDeleteAsRootIsSuccessful(): void
    {
        $this->client->request('DELETE', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->rootToken]);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteAsNormalUserReturns404(): void
    {
        $this->client->request('DELETE', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_Authorization' => 'Bearer ' . $this->normalUserToken]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnauthenticatedAccessReturns401StandardForBearer(): void
    {
        $this->client->request('GET', '/v1/api/users/' . $this->normalUser->getId(), [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGenericExceptionReturnsJson500(): void
    {
        // Create a mock of UserFactory that throws a generic exception
        $mockUserFactory = $this->createStub(UserFactory::class);
        $mockUserFactory->method('createFromDto')->will($this->throwException(new \Exception('An error appeared!')));

        // Replace the real service with our mock in the container
        static::getContainer()->set(UserFactory::class, $mockUserFactory);

        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_Authorization' => 'Bearer ' . $this->rootToken],
            json_encode(['login' => 'test', 'phone' => '12345678', 'password' => 'test'])
        );

        $this->assertResponseStatusCodeSame(500);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('An unexpected error occurred.', $response['detail']);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
