<?php

namespace App\Controller;

use App\Entity\Commande;
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

        $commandes = $this->commandeRepository->findAll();
        $chiffreAffaires = 0.0;
        $commandesParMois = [];
        $statuts = [];

        foreach ($commandes as $commande) {
            if ($commande->getStatut() !== Commande::STATUT_ANNULEE) {
                $chiffreAffaires += (float) $commande->getTotal();
            }

            $mois = $commande->getDateCreation()?->format('Y-m') ?? 'inconnu';
            $commandesParMois[$mois] = ($commandesParMois[$mois] ?? 0) + 1;
            $statuts[$commande->getStatut()] = ($statuts[$commande->getStatut()] ?? 0) + 1;
        }

        ksort($commandesParMois);

        return $this->json([
            'utilisateurs' => count($this->utilisateurRepository->findAll()),
            'produits' => count($this->produitRepository->findAll()),
            'commandes' => count($commandes),
            'avis' => count($this->avisRepository->findAll()),
            'chiffreAffaires' => round($chiffreAffaires, 2),
            'commandesParMois' => array_map(fn ($mois, $total) => ['mois' => $mois, 'total' => $total], array_keys($commandesParMois), array_values($commandesParMois)),
            'statutsCommandes' => $statuts,
        ]);
    }

    #[Route('/products', name: 'api_admin_products', methods: ['GET'])]
    public function produits(Request $request): JsonResponse
    {
        $this->exigerAdmin();

        $pagination = $this->paginateur->depuisRequete($request, 10, 50);
        $qb = $this->produitRepository->creerRequeteFiltree(['actifsSeulement' => false]);
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
