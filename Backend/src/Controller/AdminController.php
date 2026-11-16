<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use App\Repository\CommandeRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Service\JsonSerializer;
use App\Service\Paginateur;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly ProduitRepository $produitRepository,
        private readonly CategorieRepository $categorieRepository,
        private readonly CommandeRepository $commandeRepository,
        private readonly AvisRepository $avisRepository,
        private readonly Paginateur $paginateur,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/stats', name: 'api_admin_stats', methods: ['GET'])]
    public function statistiques(): JsonResponse
    {
        $this->exigerAdmin();

        $statsCommandes = $this->commandeRepository->obtenirStatistiquesAdmin();

        return $this->json([
            'utilisateurs' => $this->utilisateurRepository->count([]),
            'produits' => $this->produitRepository->count([]),
            'commandes' => $statsCommandes['total'],
            'avis' => $this->avisRepository->count([]),
            'chiffreAffaires' => $statsCommandes['chiffreAffaires'],
            'commandesParMois' => $statsCommandes['commandesParMois'],
            'statutsCommandes' => $statsCommandes['statuts'],
        ]);
    }

    #[Route('/products', name: 'api_admin_products', methods: ['GET'])]
    public function produits(Request $request): JsonResponse
    {
        $this->exigerAdmin();

        $pagination = $this->paginateur->depuisRequete($request, 10, 50);
        $qb = $this->produitRepository->creerRequeteFiltree([
            'actifsSeulement' => false,
            'tri' => (string) $request->query->get('tri', 'recent'),
        ]);
        $resultat = $this->paginateur->executer($qb, 'p', $pagination['page'], $pagination['limit']);

        return $this->jsonPage(
            $resultat['items'],
            $resultat['total'],
            $pagination['page'],
            $pagination['limit'],
            ['produit:lecture', 'categorie:lecture'],
        );
    }

    #[Route('/categories', name: 'api_admin_categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        $this->exigerAdmin();

        return $this->jsonData(
            $this->categorieRepository->findBy([], ['nom' => 'ASC']),
            ['categorie:lecture'],
        );
    }
}
