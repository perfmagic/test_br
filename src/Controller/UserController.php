<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/v1/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserFactory $userFactory,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    #[Route('', name: 'app_user_create', methods: ['POST'])]
    public function create(#[MapRequestPayload(validationGroups: ['create'])] UserDto $dto): JsonResponse
    {
        $user = $this->userFactory->createFromDto($dto);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json($user, Response::HTTP_CREATED, context: ['groups' => 'user:read']);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user || !$this->authorizationChecker->isGranted(UserVoter::VIEW, $user)) {
            throw new NotFoundHttpException('User not found');
        }

        return $this->json($user, context: ['groups' => 'user:read']);
    }

    #[Route('/{id}', name: 'app_user_update', methods: ['PUT'])]
    public function update(#[MapRequestPayload(validationGroups: ['update'])] UserDto $dto, int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user || !$this->authorizationChecker->isGranted(UserVoter::EDIT, $user)) {
            throw new NotFoundHttpException('User not found');
        }

        $this->userFactory->updateFromDto($user, $dto);

        $this->entityManager->flush();

        return $this->json(['id' => $user->getId()]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user || !$this->authorizationChecker->isGranted(UserVoter::DELETE, $user)) {
            throw new NotFoundHttpException('User not found');
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
