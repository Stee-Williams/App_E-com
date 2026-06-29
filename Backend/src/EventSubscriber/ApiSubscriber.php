<?php

namespace App\EventSubscriber;

use App\Repository\UtilisateurRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 9],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if ($request->getMethod() === 'OPTIONS') {
            $event->setResponse(new Response('', Response::HTTP_NO_CONTENT));
            return;
        }

        if ($this->isPublicRoute($request->getPathInfo(), $request->getMethod())) {
            return;
        }

        $authorization = $request->headers->get('Authorization', '');
        if (!str_starts_with($authorization, 'Bearer ')) {
            $event->setResponse(new JsonResponse(['erreur' => 'Authentification requise.'], Response::HTTP_UNAUTHORIZED));
            return;
        }

        $utilisateur = $this->utilisateurRepository->findByJetonApi(substr($authorization, 7));
        if (!$utilisateur) {
            $event->setResponse(new JsonResponse(['erreur' => 'Jeton invalide ou expiré.'], Response::HTTP_UNAUTHORIZED));
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/api/admin') && !in_array('ROLE_ADMIN', $utilisateur->getRoles(), true)) {
            $event->setResponse(new JsonResponse(['erreur' => 'Accès refusé.'], Response::HTTP_FORBIDDEN));
            return;
        }

        $request->attributes->set('utilisateur', $utilisateur);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    private function isPublicRoute(string $path, string $method): bool
    {
        if (preg_match('#^/api/auth/(login|register|forgot-password|reset-password)$#', $path)) {
            return true;
        }

        if ($method === 'GET' && (str_starts_with($path, '/api/products') || str_starts_with($path, '/api/categories'))) {
            return true;
        }

        if ($method === 'POST' && $path === '/api/orders/guest') {
            return true;
        }

        if ($method === 'GET' && (str_starts_with($path, '/api/orders/track') || $path === '/api/orders/track')) {
            return true;
        }

        if ($method === 'POST' && $path === '/api/coupons/validate') {
            return true;
        }

        return false;
    }
}
