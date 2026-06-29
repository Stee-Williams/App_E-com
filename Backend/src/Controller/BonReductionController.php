<?php

namespace App\Controller;

use App\Entity\BonReduction;
use App\Repository\BonReductionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use App\Service\NotificationService;
use App\Service\Paginateur;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/coupons')]
class BonReductionController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly BonReductionRepository $bonReductionRepository,
        private readonly Paginateur $paginateur,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('', name: 'api_coupons_list', methods: ['GET'])]
    public function liste(Request $request): JsonResponse
    {
        $this->exigerAdmin();

        $pagination = $this->paginateur->depuisRequete($request, 10, 50);
        $qb = $this->bonReductionRepository->creerRequeteListe();
        $resultat = $this->paginateur->executer($qb, 'b', $pagination['page'], $pagination['limit']);

        return $this->jsonPage(
            $resultat['items'],
            $resultat['total'],
            $pagination['page'],
            $pagination['limit'],
            ['bon_reduction:lecture', 'admin:lecture'],
        );
    }

    #[Route('/validate', name: 'api_coupons_validate', methods: ['POST'])]
    public function valider(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $code = (string) ($data['code'] ?? '');
        $montant = (float) ($data['montant'] ?? $data['amount'] ?? 0);

        $bon = $this->bonReductionRepository->findByCode($code);
        if (!$bon || !$bon->estValide($montant)) {
            return $this->jsonErreur('Code promo invalide ou expiré.');
        }

        return $this->json([
            'valide' => true,
            'reduction' => $bon->calculerReduction($montant),
            'bon' => $this->serializer->normalize($bon, ['bon_reduction:lecture']),
        ]);
    }

    #[Route('', name: 'api_coupons_create', methods: ['POST'])]
    public function creer(Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $data = json_decode($request->getContent(), true) ?? [];

        $bon = new BonReduction();
        $bon->setCode((string) ($data['code'] ?? ''));
        $bon->setType((string) ($data['type'] ?? BonReduction::TYPE_POURCENTAGE));
        $bon->setValeur(number_format((float) ($data['valeur'] ?? 0), 2, '.', ''));
        $bon->setMontantMinimum(isset($data['montantMinimum']) ? number_format((float) $data['montantMinimum'], 2, '.', '') : null);
        $bon->setUtilisationsMax(isset($data['utilisationsMax']) ? (int) $data['utilisationsMax'] : null);
        $bon->setActif((bool) ($data['actif'] ?? true));

        if (!empty($data['dateDebut'])) {
            $bon->setDateDebut(new \DateTimeImmutable($data['dateDebut']));
        }
        if (!empty($data['dateFin'])) {
            $bon->setDateFin(new \DateTimeImmutable($data['dateFin']));
        }

        $this->em->persist($bon);
        $this->em->flush();

        if ($bon->isActif()) {
            $this->notificationService->notifierPromo($bon);
        }

        return $this->jsonData($bon, ['bon_reduction:lecture'], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_coupons_update', methods: ['PATCH'])]
    public function modifier(int $id, Request $request): JsonResponse
    {
        $this->exigerAdmin();
        $bon = $this->bonReductionRepository->find($id);
        if (!$bon) {
            return $this->jsonErreur('Bon de réduction introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (isset($data['code'])) {
            $bon->setCode((string) $data['code']);
        }
        if (isset($data['type'])) {
            $bon->setType((string) $data['type']);
        }
        if (isset($data['valeur'])) {
            $bon->setValeur(number_format((float) $data['valeur'], 2, '.', ''));
        }
        if (array_key_exists('actif', $data)) {
            $bon->setActif((bool) $data['actif']);
        }

        $this->em->flush();

        return $this->jsonData($bon, ['bon_reduction:lecture']);
    }

    #[Route('/{id}', name: 'api_coupons_delete', methods: ['DELETE'])]
    public function supprimer(int $id): JsonResponse
    {
        $this->exigerAdmin();
        $bon = $this->bonReductionRepository->find($id);
        if (!$bon) {
            return $this->jsonErreur('Bon de réduction introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($bon);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
