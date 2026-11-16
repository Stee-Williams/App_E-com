<?php

namespace App\Controller;

use App\Service\GeographieProviderException;
use App\Service\SenegalGeographieService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/geographie')]
class GeographieController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly SenegalGeographieService $geographieService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/suggestions', name: 'api_geographie_suggestions', methods: ['GET'])]
    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        if (mb_strlen($query) < 2 || mb_strlen($query) > 100) {
            return $this->jsonErreur('La recherche doit contenir entre 2 et 100 caractères.');
        }

        $limitRaw = $request->query->get('limit');
        $limit = $limitRaw === null ? 10 : filter_var($limitRaw, FILTER_VALIDATE_INT);
        if ($limit === false || $limit < 1 || $limit > 20) {
            return $this->jsonErreur('Le nombre de suggestions doit être compris entre 1 et 20.');
        }

        try {
            return $this->json($this->geographieService->suggestions($query, $limit));
        } catch (GeographieProviderException $e) {
            return $this->jsonErreur($e->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
