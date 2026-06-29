<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use App\Service\MotDePasseService;
use App\Service\Paginateur;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UtilisateurController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly MotDePasseService $motDePasseService,
        private readonly Paginateur $paginateur,
    ) {
        parent::__construct($serializer);
    }

    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function liste(Request $request): JsonResponse
    {
        $this->exigerAdmin();

        $pagination = $this->paginateur->depuisRequete($request, 10, 50);
        $qb = $this->utilisateurRepository->creerRequeteListe();
        $resultat = $this->paginateur->executer($qb, 'u', $pagination['page'], $pagination['limit']);

        return $this->jsonPage(
            $resultat['items'],
            $resultat['total'],
            $pagination['page'],
            $pagination['limit'],
            ['utilisateur:lecture', 'admin:lecture'],
        );
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function afficher(int $id): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return $this->jsonErreur('Utilisateur introuvable.', Response::HTTP_NOT_FOUND);
        }

        $connecte = $this->getUtilisateurConnecte();
        if (!$this->estAdmin() && $connecte?->getId() !== $utilisateur->getId()) {
            return $this->jsonErreur('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        return $this->jsonData($utilisateur, ['utilisateur:lecture', 'adresse:lecture']);
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PATCH'])]
    public function modifier(int $id, Request $request): JsonResponse
    {
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return $this->jsonErreur('Utilisateur introuvable.', Response::HTTP_NOT_FOUND);
        }

        $connecte = $this->getUtilisateurConnecte();
        if (!$this->estAdmin() && $connecte?->getId() !== $utilisateur->getId()) {
            return $this->jsonErreur('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['prenom'])) {
            $utilisateur->setPrenom((string) $data['prenom']);
        }
        if (isset($data['nom'])) {
            $utilisateur->setNom((string) $data['nom']);
        }
        if (isset($data['telephone'])) {
            $utilisateur->setTelephone((string) $data['telephone']);
        }
        if ($this->estAdmin() && isset($data['roles'])) {
            $utilisateur->setRoles((array) $data['roles']);
        }
        if (!empty($data['motDePasse'])) {
            $utilisateur->setMotDePasse($this->motDePasseService->hash($utilisateur, (string) $data['motDePasse']));
        }

        $this->em->flush();

        return $this->jsonData($utilisateur, ['utilisateur:lecture']);
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function supprimer(int $id): JsonResponse
    {
        $this->exigerAdmin();
        $utilisateur = $this->utilisateurRepository->find($id);
        if (!$utilisateur) {
            return $this->jsonErreur('Utilisateur introuvable.', Response::HTTP_NOT_FOUND);
        }

        $connecte = $this->getUtilisateurConnecte();
        if ($connecte?->getId() === $utilisateur->getId()) {
            return $this->jsonErreur('Vous ne pouvez pas supprimer votre propre compte.');
        }

        $this->em->remove($utilisateur);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
