<?php

namespace App\Tests\Service;

use App\Service\GeographieProviderException;
use App\Service\SenegalGeographieService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SenegalGeographieServiceTest extends TestCase
{
    public function testSuggestionsAreNormalizedAndCached(): void
    {
        $appels = 0;
        $client = new MockHttpClient(function (string $method, string $url) use (&$appels): MockResponse {
            ++$appels;
            self::assertSame('GET', $method);
            self::assertStringContainsString('/v1/adresses/suggestions?q=thies&limit=2', $url);

            return new MockResponse(json_encode([
                'data' => [
                    [
                        'id' => 'reg_thies',
                        'level' => 'region',
                        'name' => 'Thiès',
                        'name_source' => 'THIES',
                        'parent_id' => null,
                        'contexte' => '',
                        'coordonnees' => ['lat' => 14.71, 'lon' => -16.87],
                    ],
                    [
                        'id' => 'com_thies_est',
                        'level' => 'commune',
                        'name' => null,
                        'name_source' => 'THIES EST',
                        'parent_id' => 'ville_thies',
                        'contexte' => 'Ville de Thiès',
                        'coordonnees' => null,
                    ],
                ],
                'sources' => [['title' => 'ANSD']],
            ], JSON_THROW_ON_ERROR));
        });

        $service = new SenegalGeographieService($client, new ArrayAdapter(), new NullLogger());

        $resultat = $service->suggestions(' thies ', 2);
        $resultatEnCache = $service->suggestions('THIES', 2);

        self::assertSame($resultat, $resultatEnCache);
        self::assertSame(1, $appels);
        self::assertSame('Thiès', $resultat['items'][0]['nom']);
        self::assertSame('THIES EST', $resultat['items'][1]['nom']);
        self::assertSame('commune', $resultat['items'][1]['niveau']);
        self::assertSame('ville_thies', $resultat['items'][1]['parentId']);
        self::assertSame([['title' => 'ANSD']], $resultat['sources']);
    }

    public function testProviderErrorRaisesUnavailableException(): void
    {
        $client = new MockHttpClient([
            new MockResponse('{"code":"QUOTA_EXCEEDED"}', ['http_code' => 429]),
        ]);
        $service = new SenegalGeographieService($client, new ArrayAdapter(), new NullLogger());

        $this->expectException(GeographieProviderException::class);
        $service->suggestions('Dakar');
    }
}
