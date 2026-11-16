<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SenegalGeographieService
{
    private const API_URL = 'https://apps.orvlabs.com/v1/adresses/suggestions';
    private const CACHE_TTL = 21600;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey = '',
    ) {
    }

    /**
     * @return array{items: array<int, array{id: string, niveau: string, nom: string, contexte: string, parentId: ?string, coordonnees: ?array}>, sources: array}
     */
    public function suggestions(string $query, int $limit = 10): array
    {
        $query = trim($query);
        $cacheKey = 'senegal_geographie_suggestions_' . hash('sha256', mb_strtolower($query) . ':' . $limit);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $limit): array {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->chargerSuggestions($query, $limit);
        });
    }

    /**
     * @return array{items: array<int, array{id: string, niveau: string, nom: string, contexte: string, parentId: ?string, coordonnees: ?array}>, sources: array}
     */
    private function chargerSuggestions(string $query, int $limit): array
    {
        $headers = ['Accept' => 'application/json'];
        if ($this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        try {
            $response = $this->httpClient->request('GET', self::API_URL, [
                'headers' => $headers,
                'query' => ['q' => $query, 'limit' => $limit],
                'timeout' => 5,
            ]);
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $this->logger->warning('Le fournisseur géographique a répondu avec un statut inattendu.', [
                    'status' => $status,
                ]);

                throw new GeographieProviderException('Le service de suggestions d’adresses est momentanément indisponible.');
            }
            $payload = json_decode($response->getContent(false), true, 512, JSON_THROW_ON_ERROR);
        } catch (TransportExceptionInterface|\JsonException $e) {
            $this->logger->warning('Échec de la communication avec le fournisseur géographique.', [
                'exception' => $e,
            ]);

            throw new GeographieProviderException(
                'Le service de suggestions d’adresses est momentanément indisponible.',
                previous: $e,
            );
        }

        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            $this->logger->warning('Le fournisseur géographique a retourné une réponse invalide.');

            throw new GeographieProviderException('Le service de suggestions d’adresses est momentanément indisponible.');
        }

        $items = [];
        foreach ($payload['data'] as $lieu) {
            if (!is_array($lieu) || !isset($lieu['id'], $lieu['level'])) {
                $this->logger->warning('Le fournisseur géographique a retourné un lieu invalide.');

                throw new GeographieProviderException('Le service de suggestions d’adresses est momentanément indisponible.');
            }

            $nom = $lieu['name'] ?? null;
            if (!is_string($nom) || trim($nom) === '') {
                $nom = $lieu['name_source'] ?? '';
            }

            $items[] = [
                'id' => (string) $lieu['id'],
                'niveau' => (string) $lieu['level'],
                'nom' => (string) $nom,
                'contexte' => (string) ($lieu['contexte'] ?? ''),
                'parentId' => isset($lieu['parent_id']) ? (string) $lieu['parent_id'] : null,
                'coordonnees' => isset($lieu['coordonnees']) && is_array($lieu['coordonnees'])
                    ? $lieu['coordonnees']
                    : null,
            ];
        }

        return [
            'items' => $items,
            'sources' => isset($payload['sources']) && is_array($payload['sources']) ? $payload['sources'] : [],
        ];
    }
}
