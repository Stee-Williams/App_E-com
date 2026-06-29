<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
class NotificationController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly NotificationRepository $notificationRepository,
    ) {
        parent::__construct($serializer);
    }

    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $notifications = $this->notificationRepository->findParUtilisateur($utilisateur);
        $nonLues = $this->notificationRepository->compterNonLues($utilisateur);

        return $this->json([
            'items' => $this->serializer->normalize($notifications, ['notification:lecture']),
            'nonLues' => $nonLues,
        ]);
    }

    #[Route('/{id}/read', name: 'api_notifications_read', methods: ['PATCH'])]
    public function marquerLue(int $id): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);
        if (!$notification || $notification->getUtilisateur()?->getId() !== $utilisateur->getId()) {
            return $this->jsonErreur('Notification introuvable.', Response::HTTP_NOT_FOUND);
        }

        $notification->setLu(true);
        $this->em->flush();

        return $this->jsonData($notification, ['notification:lecture']);
    }

    #[Route('/read-all', name: 'api_notifications_read_all', methods: ['PATCH'])]
    public function toutMarquerLu(): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $notifications = $this->notificationRepository->findParUtilisateur($utilisateur, 100);
        foreach ($notifications as $notification) {
            $notification->setLu(true);
        }
        $this->em->flush();

        return $this->json(['message' => 'Toutes les notifications ont été lues.']);
    }
}
