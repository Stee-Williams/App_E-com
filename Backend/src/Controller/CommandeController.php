<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\VarianteProduit;
use App\Repository\CommandeRepository;
use App\Service\CommandeService;
use App\Service\NotificationService;
use App\Service\Paginateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class CommandeController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly CommandeRepository $commandeRepository,
        private readonly CommandeService $commandeService,
        private readonly NotificationService $notificationService,
        private readonly Paginateur $paginateur,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/my', name: 'api_orders_my', methods: ['GET'])]
    public function mesCommandes(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $pagination = $this->paginateur->depuisRequete($request, 10, 50);
        $qb = $this->commandeRepository->creerRequeteParUtilisateur($utilisateur->getId());
        $resultat = $this->paginateur->executer($qb, 'c', $pagination['page'], $pagination['limit']);

        return $this->jsonPage(
            $resultat['items'],
            $resultat['total'],
            $pagination['page'],
            $pagination['limit'],
            ['commande:lecture', 'produit:lecture', 'categorie:lecture', 'adresse:lecture', 'bon_reduction:lecture'],
        );
    }

    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function liste(Request $request): JsonResponse
    {
        $this->exigerAdmin();

        $pagination = $this->paginateur->depuisRequete($request, 10, 50);
        $qb = $this->commandeRepository->creerRequeteListe();
        $resultat = $this->paginateur->executer($qb, 'c', $pagination['page'], $pagination['limit']);

        return $this->jsonPage(
            $resultat['items'],
            $resultat['total'],
            $pagination['page'],
            $pagination['limit'],
            ['commande:lecture', 'admin:lecture', 'utilisateur:lecture', 'produit:lecture'],
        );
    }

    #[Route('/guest', name: 'api_orders_guest', methods: ['POST'])]
    public function creerInvite(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $articles = $data['articles'] ?? $data['items'] ?? [];

        try {
            $commande = $this->commandeService->creerCommandeInvite(
                [
                    'email' => $data['email'] ?? '',
                    'prenom' => $data['prenom'] ?? '',
                    'nom' => $data['nom'] ?? '',
                    'telephone' => $data['telephone'] ?? null,
                    'adresse' => $data['adresse'] ?? [],
                ],
                $articles,
                $data['codeBon'] ?? $data['couponCode'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->jsonErreur($e->getMessage());
        }

        return $this->json([
            'commande' => $this->serializer->normalize($commande, ['commande:lecture', 'produit:lecture']),
            'jetonSuivi' => $commande->getJetonSuivi(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/track', name: 'api_orders_track', methods: ['GET'])]
    public function suivre(Request $request): JsonResponse
    {
        $jeton = trim((string) $request->query->get('jeton', ''));
        if ($jeton !== '') {
            $commande = $this->commandeRepository->findParJetonSuivi($jeton);
            if (!$commande) {
                return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
            }

            return $this->jsonData($commande, ['commande:lecture', 'produit:lecture', 'categorie:lecture']);
        }

        $numero = trim((string) $request->query->get('numero', ''));
        $email = trim((string) $request->query->get('email', ''));
        if ($numero === '' || $email === '') {
            return $this->jsonErreur('Jeton ou numéro + email requis.');
        }

        $commande = $this->commandeRepository->findParNumeroEtEmail($numero, $email);
        if (!$commande) {
            return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonData($commande, ['commande:lecture', 'produit:lecture', 'categorie:lecture']);
    }

    #[Route('', name: 'api_orders_create', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $articles = $data['articles'] ?? $data['items'] ?? [];

        try {
            $commande = $this->commandeService->creerCommande(
                $utilisateur,
                $articles,
                isset($data['adresseId']) ? (int) $data['adresseId'] : null,
                $data['codeBon'] ?? $data['couponCode'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->jsonErreur($e->getMessage());
        }

        return $this->jsonData($commande, ['commande:lecture', 'produit:lecture', 'adresse:lecture'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_orders_show', methods: ['GET'])]
    public function afficher(int $id): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        if (!$commande) {
            return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $utilisateur = $this->getUtilisateurConnecte();
        if (!$this->estAdmin() && $commande->getUtilisateur()?->getId() !== $utilisateur?->getId()) {
            return $this->jsonErreur('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        return $this->jsonData($commande, ['commande:lecture', 'produit:lecture', 'adresse:lecture', 'utilisateur:lecture']);
    }

    #[Route('/{id}/status', name: 'api_orders_status', methods: ['PATCH'])]
    public function changerStatut(int $id, Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $commande = $this->commandeRepository->find($id);
        if (!$commande) {
            return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $statut = (string) ($data['statut'] ?? $data['status'] ?? '');
        if (!in_array($statut, [Commande::STATUT_EN_ATTENTE, Commande::STATUT_CONFIRMEE, Commande::STATUT_EXPEDIEE, Commande::STATUT_LIVREE, Commande::STATUT_ANNULEE], true)) {
            return $this->jsonErreur('Statut invalide.');
        }

        $ancienStatut = $commande->getStatut();
        $commande->setStatut($statut);
        $this->em->flush();

        $this->notificationService->notifierStatutCommande($commande, $ancienStatut);

        return $this->jsonData($commande, ['commande:lecture', 'admin:lecture']);
    }

    #[Route('/{id}/cancel', name: 'api_orders_cancel', methods: ['POST'])]
    public function annuler(int $id): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        if (!$commande) {
            return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $utilisateur = $this->getUtilisateurConnecte();
        if (!$this->estAdmin() && $commande->getUtilisateur()?->getId() !== $utilisateur?->getId()) {
            return $this->jsonErreur('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        if ($commande->getStatut() === Commande::STATUT_ANNULEE) {
            return $this->jsonErreur('Commande déjà annulée.');
        }

        if (in_array($commande->getStatut(), [Commande::STATUT_EXPEDIEE, Commande::STATUT_LIVREE], true)) {
            return $this->jsonErreur('Cette commande ne peut plus être annulée.');
        }

        foreach ($commande->getLignes() as $ligne) {
            $produit = $ligne->getProduit();
            if ($produit) {
                $variante = $ligne->getVariante();
                if ($variante) {
                    $variante->setStock($variante->getStock() + $ligne->getQuantite());
                } else {
                    $produit->setStock($produit->getStock() + $ligne->getQuantite());
                }
            }
        }

        $ancienStatut = $commande->getStatut();
        $commande->setStatut(Commande::STATUT_ANNULEE);
        $this->em->flush();

        $this->notificationService->notifierStatutCommande($commande, $ancienStatut);

        return $this->jsonData($commande, ['commande:lecture']);
    }

    #[Route('/{id}/invoice', name: 'api_orders_invoice', methods: ['GET'])]
    public function facture(int $id, Request $request): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        if (!$commande) {
            return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        if (!$this->peutAccederFacture($commande, $request)) {
            return $this->jsonErreur('Accès refusé.', Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->construireFacture($commande));
    }

    #[Route('/track/invoice', name: 'api_orders_track_invoice', methods: ['GET'])]
    public function factureInvite(Request $request): JsonResponse
    {
        $jeton = trim((string) $request->query->get('jeton', ''));
        if ($jeton === '') {
            return $this->jsonErreur('Jeton requis.');
        }

        $commande = $this->commandeRepository->findParJetonSuivi($jeton);
        if (!$commande) {
            return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->construireFacture($commande));
    }

    private function peutAccederFacture(Commande $commande, Request $request): bool
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if ($this->estAdmin() || $commande->getUtilisateur()?->getId() === $utilisateur?->getId()) {
            return true;
        }

        $jeton = trim((string) $request->query->get('jeton', ''));

        return $jeton !== '' && $commande->getJetonSuivi() === $jeton;
    }

    /** @return array<string, mixed> */
    private function construireFacture(Commande $commande): array
    {
        return [
            'numero' => $commande->getNumero(),
            'date' => $commande->getDateCreation()?->format('d/m/Y'),
            'client' => $commande->getNomClient(),
            'email' => $commande->getUtilisateur()?->getEmail() ?? $commande->getEmailInvite(),
            'adresse' => $commande->getAdresseLivraisonComplete(),
            'sousTotal' => $commande->getSousTotal(),
            'fraisLivraison' => $commande->getFraisLivraison(),
            'reduction' => $commande->getReduction(),
            'total' => $commande->getTotal(),
            'statut' => $commande->getStatut(),
            'lignes' => $this->serializer->normalize($commande->getLignes(), ['commande:lecture', 'produit:lecture']),
        ];
    }
}
