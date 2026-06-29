<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Repository\CategorieRepository;
use App\Service\SlugService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories')]
class CategorieController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly CategorieRepository $categorieRepository,
        private readonly SlugService $slugService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function liste(): JsonResponse
    {
        return $this->jsonData($this->categorieRepository->findAll(), ['categorie:lecture']);
    }

    #[Route('/{id}', name: 'api_categories_show', methods: ['GET'])]
    public function afficher(int $id): JsonResponse
    {
        $categorie = $this->categorieRepository->find($id);
        if (!$categorie) {
            return $this->jsonErreur('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonData($categorie, ['categorie:lecture']);
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $data = json_decode($request->getContent(), true) ?? [];

        $categorie = new Categorie();
        $categorie->setNom((string) ($data['nom'] ?? ''));
        $categorie->setSlug($this->slugService->generer($categorie->getNom()));
        $categorie->setDescription($data['description'] ?? null);
        $categorie->setImage($data['image'] ?? null);

        $this->em->persist($categorie);
        $this->em->flush();

        return $this->jsonData($categorie, ['categorie:lecture'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_categories_update', methods: ['PATCH'])]
    public function modifier(int $id, Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $categorie = $this->categorieRepository->find($id);
        if (!$categorie) {
            return $this->jsonErreur('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['nom'])) {
            $categorie->setNom((string) $data['nom']);
            $categorie->setSlug($this->slugService->generer($categorie->getNom()));
        }
        if (array_key_exists('description', $data)) {
            $categorie->setDescription($data['description']);
        }
        if (array_key_exists('image', $data)) {
            $categorie->setImage($data['image']);
        }

        $this->em->flush();

        return $this->jsonData($categorie, ['categorie:lecture']);
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    public function supprimer(int $id): JsonResponse
    {
        $this->exigerAdmin();
        $categorie = $this->categorieRepository->find($id);
        if (!$categorie) {
            return $this->jsonErreur('Catégorie introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($categorie);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
