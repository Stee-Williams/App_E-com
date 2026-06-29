<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\JsonSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class ApiController extends AbstractController
{
    public function __construct(
        protected readonly JsonSerializer $serializer,
    ) {
    }

    protected function jsonData(mixed $data, array $groups = [], int $status = Response::HTTP_OK): JsonResponse
    {
        $json = $this->serializer->serialize($data, $groups);

        return new JsonResponse($json, $status, [], true);
    }

    /** @param array<int, mixed> $items */
    protected function jsonPage(array $items, int $total, int $page, int $limit, array $groups = [], int $status = Response::HTTP_OK): JsonResponse
    {
        $pages = max(1, (int) ceil($total / max(1, $limit)));
        $payload = [
            'items' => $this->serializer->normalize($items, $groups),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
        ];

        return new JsonResponse(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $status, [], true);
    }

    protected function jsonErreur(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json(['erreur' => $message], $status);
    }

    protected function getUtilisateurConnecte(): ?Utilisateur
    {
        $utilisateur = $this->container->get('request_stack')->getCurrentRequest()?->attributes->get('utilisateur');

        return $utilisateur instanceof Utilisateur ? $utilisateur : null;
    }

    protected function estAdmin(): bool
    {
        $utilisateur = $this->getUtilisateurConnecte();

        return $utilisateur !== null && in_array('ROLE_ADMIN', $utilisateur->getRoles(), true);
    }

    protected function exigerAdmin(): void
    {
        if (!$this->estAdmin()) {
            throw new AccessDeniedHttpException('Accès refusé.');
        }
    }

    protected function exigerConnecte(): void
    {
        if (!$this->getUtilisateurConnecte()) {
            throw new AccessDeniedHttpException('Authentification requise.');
        }
    }
}
