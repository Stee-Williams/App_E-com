<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\ImageProduit;
use App\Entity\Produit;
use App\Entity\VarianteProduit;
use App\Repository\AvisRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use App\Service\Paginateur;
use App\Service\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProduitController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly ProduitRepository $produitRepository,
        private readonly CategorieRepository $categorieRepository,
        private readonly AvisRepository $avisRepository,
        private readonly SlugService $slugService,
        private readonly Paginateur $paginateur,
        private readonly string $projectDir,
    ) {
        parent::__construct($serializer);
    }

    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function liste(Request $request): JsonResponse
    {
        $prixMin = $request->query->get('prixMin');
        $prixMax = $request->query->get('prixMax');

        $filtres = [
            'q' => $request->query->get('q'),
            'categorie' => $request->query->getInt('categorie') ?: null,
            'prixMin' => is_numeric($prixMin) ? (float) $prixMin : null,
            'prixMax' => is_numeric($prixMax) ? (float) $prixMax : null,
            'promo' => $request->query->getBoolean('promo'),
            'enStock' => $request->query->getBoolean('enStock'),
            'tri' => $request->query->get('tri', 'recent'),
        ];

        $pagination = $this->paginateur->depuisRequete($request, 12, 48);
        $qb = $this->produitRepository->creerRequeteFiltree($filtres);
        $resultat = $this->paginateur->executer($qb, 'p', $pagination['page'], $pagination['limit']);

        return $this->jsonPage(
            $resultat['items'],
            $resultat['total'],
            $pagination['page'],
            $pagination['limit'],
            ['produit:lecture', 'categorie:lecture', 'variante:lecture'],
        );
    }

    #[Route('/{id}', name: 'api_products_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function afficher(int $id): JsonResponse
    {
        $produit = $this->produitRepository->find($id);
        if (!$produit || (!$produit->isActif() && !$this->estAdmin())) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonData($produit, ['produit:lecture', 'categorie:lecture', 'avis:lecture', 'utilisateur:lecture', 'variante:lecture']);
    }

    #[Route('/{id}/similar', name: 'api_products_similar', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function similaires(int $id): JsonResponse
    {
        $produit = $this->produitRepository->find($id);
        if (!$produit || !$produit->isActif()) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $similaires = $this->produitRepository->findSimilaires($produit);

        return $this->jsonData($similaires, ['produit:lecture', 'categorie:lecture', 'variante:lecture']);
    }

    #[Route('/{id}/variants', name: 'api_products_variants_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ajouterVariante(int $id, Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $variante = new VarianteProduit();
        $variante->setTaille($data['taille'] ?? null);
        $variante->setCouleur($data['couleur'] ?? null);
        $variante->setStock((int) ($data['stock'] ?? 0));
        $variante->setActif((bool) ($data['actif'] ?? true));
        $produit->addVariante($variante);

        $this->em->flush();

        return $this->jsonData($variante, ['variante:lecture'], Response::HTTP_CREATED);
    }

    #[Route('/{id}/variants/{varianteId}', name: 'api_products_variants_delete', methods: ['DELETE'], requirements: ['id' => '\d+', 'varianteId' => '\d+'])]
    public function supprimerVariante(int $id, int $varianteId): JsonResponse
    {
        $this->exigerAdmin();
        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        foreach ($produit->getVariantes() as $variante) {
            if ($variante->getId() === $varianteId) {
                $this->em->remove($variante);
                $this->em->flush();

                return $this->json(null, Response::HTTP_NO_CONTENT);
            }
        }

        return $this->jsonErreur('Variante introuvable.', Response::HTTP_NOT_FOUND);
    }

    #[Route('', name: 'api_products_create', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $data = json_decode($request->getContent(), true) ?? [];

        $categorie = $this->categorieRepository->find($data['categorieId'] ?? 0);
        if (!$categorie) {
            return $this->jsonErreur('Catégorie introuvable.');
        }

        $produit = new Produit();
        $produit->setNom((string) ($data['nom'] ?? ''));
        $produit->setSlug($this->slugService->generer($produit->getNom()));
        $produit->setDescription($data['description'] ?? null);
        $produit->setPrix(number_format((float) ($data['prix'] ?? 0), 2, '.', ''));
        $produit->setPrixPromo(isset($data['prixPromo']) ? number_format((float) $data['prixPromo'], 2, '.', '') : null);
        $produit->setStock((int) ($data['stock'] ?? 0));
        $produit->setActif((bool) ($data['actif'] ?? true));
        $produit->setCategorie($categorie);

        $this->em->persist($produit);
        $this->em->flush();

        return $this->jsonData($produit, ['produit:lecture', 'categorie:lecture'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_products_update', methods: ['PATCH'])]
    public function modifier(int $id, Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['nom'])) {
            $produit->setNom((string) $data['nom']);
            $produit->setSlug($this->slugService->generer($produit->getNom()));
        }
        if (array_key_exists('description', $data)) {
            $produit->setDescription($data['description']);
        }
        if (isset($data['prix'])) {
            $produit->setPrix(number_format((float) $data['prix'], 2, '.', ''));
        }
        if (array_key_exists('prixPromo', $data)) {
            $produit->setPrixPromo($data['prixPromo'] !== null ? number_format((float) $data['prixPromo'], 2, '.', '') : null);
        }
        if (isset($data['stock'])) {
            $produit->setStock((int) $data['stock']);
        }
        if (isset($data['actif'])) {
            $produit->setActif((bool) $data['actif']);
        }
        if (isset($data['categorieId'])) {
            $categorie = $this->categorieRepository->find($data['categorieId']);
            if ($categorie) {
                $produit->setCategorie($categorie);
            }
        }

        $this->em->flush();

        return $this->jsonData($produit, ['produit:lecture', 'categorie:lecture']);
    }

    #[Route('/{id}', name: 'api_products_delete', methods: ['DELETE'])]
    public function supprimer(int $id): JsonResponse
    {
        $this->exigerAdmin();
        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($produit);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/image', name: 'api_products_image', methods: ['POST'])]
    public function uploadImage(int $id, Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        /** @var UploadedFile|null $fichier */
        $fichier = $request->files->get('image');
        if (!$fichier) {
            return $this->jsonErreur('Aucune image fournie.');
        }

        $chemin = $this->sauvegarderImage($fichier);
        $produit->setImagePrincipale($chemin);
        $this->em->flush();

        return $this->jsonData($produit, ['produit:lecture']);
    }

    #[Route('/{id}/images', name: 'api_products_images', methods: ['POST'])]
    public function uploadImages(int $id, Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $fichiers = $request->files->all();
        $ordre = $produit->getImages()->count();
        foreach ($fichiers as $fichier) {
            if ($fichier instanceof UploadedFile) {
                $image = new ImageProduit();
                $image->setChemin($this->sauvegarderImage($fichier));
                $image->setOrdre($ordre++);
                $produit->addImage($image);
            }
        }

        $this->em->flush();

        return $this->jsonData($produit, ['produit:lecture']);
    }

    #[Route('/{id}/reviews', name: 'api_products_reviews_list', methods: ['GET'])]
    public function listeAvis(int $id): JsonResponse
    {
        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonData($produit->getAvis()->toArray(), ['avis:lecture', 'utilisateur:lecture']);
    }

    #[Route('/{id}/reviews', name: 'api_products_reviews_create', methods: ['POST'])]
    public function creerAvis(int $id, Request $request): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        $produit = $this->produitRepository->find($id);
        if (!$produit) {
            return $this->jsonErreur('Produit introuvable.', Response::HTTP_NOT_FOUND);
        }

        $existant = $this->avisRepository->findOneBy(['produit' => $produit, 'utilisateur' => $utilisateur]);
        if ($existant) {
            return $this->jsonErreur('Vous avez déjà laissé un avis pour ce produit.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $avis = new Avis();
        $avis->setProduit($produit);
        $avis->setUtilisateur($utilisateur);
        $avis->setNote((int) ($data['note'] ?? 5));
        $avis->setCommentaire($data['commentaire'] ?? null);

        $this->em->persist($avis);
        $this->em->flush();

        return $this->jsonData($avis, ['avis:lecture', 'utilisateur:lecture'], Response::HTTP_CREATED);
    }

    private function sauvegarderImage(UploadedFile $fichier): string
    {
        $dossier = $this->projectDir . '/public/uploads/produits';
        if (!is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $nom = uniqid('produit_', true) . '.' . $fichier->guessExtension();
        $fichier->move($dossier, $nom);

        return '/uploads/produits/' . $nom;
    }
}
