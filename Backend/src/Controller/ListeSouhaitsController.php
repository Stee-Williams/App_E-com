<?php

namespace App\Controller;

use App\Entity\ElementListeSouhaits;
use App\Repository\ElementListeSouhaitsRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/wishlist')]
class ListeSouhaitsController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly ElementListeSouhaitsRepository $listeSouhaitsRepository,
        private readonly ProduitRepository $produitRepository,
    ) {
        parent::__construct($serializer);
    }

    #[Route('', name: 'api_wishlist_list', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $elements = $this->listeSouhaitsRepository->findByUtilisateur($utilisateur->getId());

        return $this->jsonData($elements, ['liste_souhaits:lecture', 'produit:lecture', 'categorie:lecture']);
    }

    #[Route('', name: 'api_wishlist_add', methods: ['POST'])]
    public function ajouter(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $produitId = (int) ($data['produitId'] ?? $data['productId'] ?? 0);
        $produit = $this->produitRepository->find($produitId);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.');
        }

        $existant = $this->listeSouhaitsRepository->findOneBy(['utilisateur' => $utilisateur, 'produit' => $produit]);
        if ($existant) {
            return $this->jsonData($existant, ['liste_souhaits:lecture', 'produit:lecture']);
        }

        $element = new ElementListeSouhaits();
        $element->setUtilisateur($utilisateur);
        $element->setProduit($produit);

        $this->em->persist($element);
        $this->em->flush();

        return $this->jsonData($element, ['liste_souhaits:lecture', 'produit:lecture'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_wishlist_remove', methods: ['DELETE'])]
    public function supprimer(int $id): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        $element = $this->listeSouhaitsRepository->find($id);
        if (!$element || $element->getUtilisateur()?->getId() !== $utilisateur?->getId()) {
            return $this->jsonErreur('Élément introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($element);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
