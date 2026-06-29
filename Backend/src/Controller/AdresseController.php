<?php

namespace App\Controller;

use App\Entity\Adresse;
use App\Repository\AdresseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/addresses')]
class AdresseController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly AdresseRepository $adresseRepository,
    ) {
        parent::__construct($serializer);
    }

    #[Route('', name: 'api_addresses_list', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        return $this->jsonData($utilisateur->getAdresses()->toArray(), ['adresse:lecture']);
    }

    #[Route('', name: 'api_addresses_create', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $adresse = $this->remplirAdresse(new Adresse(), $data);
        $adresse->setUtilisateur($utilisateur);

        if ($adresse->isParDefaut()) {
            $this->reinitialiserParDefaut($utilisateur);
        }

        $this->em->persist($adresse);
        $this->em->flush();

        return $this->jsonData($adresse, ['adresse:lecture'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_addresses_update', methods: ['PATCH'])]
    public function modifier(int $id, Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        $adresse = $this->adresseRepository->find($id);
        if (!$adresse || $adresse->getUtilisateur()?->getId() !== $utilisateur?->getId()) {
            return $this->jsonErreur('Adresse introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $this->remplirAdresse($adresse, $data);

        if ($adresse->isParDefaut()) {
            $this->reinitialiserParDefaut($utilisateur, $adresse->getId());
        }

        $this->em->flush();

        return $this->jsonData($adresse, ['adresse:lecture']);
    }

    #[Route('/{id}', name: 'api_addresses_delete', methods: ['DELETE'])]
    public function supprimer(int $id): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        $adresse = $this->adresseRepository->find($id);
        if (!$adresse || $adresse->getUtilisateur()?->getId() !== $utilisateur?->getId()) {
            return $this->jsonErreur('Adresse introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($adresse);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function remplirAdresse(Adresse $adresse, array $data): Adresse
    {
        if (isset($data['libelle'])) {
            $adresse->setLibelle((string) $data['libelle']);
        }
        if (isset($data['rue'])) {
            $adresse->setRue((string) $data['rue']);
        }
        if (isset($data['ville'])) {
            $adresse->setVille((string) $data['ville']);
        }
        if (isset($data['codePostal'])) {
            $adresse->setCodePostal((string) $data['codePostal']);
        }
        if (isset($data['pays'])) {
            $adresse->setPays((string) $data['pays']);
        }
        if (isset($data['parDefaut'])) {
            $adresse->setParDefaut((bool) $data['parDefaut']);
        }

        return $adresse;
    }

    private function reinitialiserParDefaut(\App\Entity\Utilisateur $utilisateur, ?int $exclureId = null): void
    {
        foreach ($utilisateur->getAdresses() as $adresse) {
            if ($exclureId === null || $adresse->getId() !== $exclureId) {
                $adresse->setParDefaut(false);
            }
        }
    }
}
