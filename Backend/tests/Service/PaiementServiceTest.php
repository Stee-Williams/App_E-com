<?php

namespace App\Tests\Service;

use App\Repository\CommandeRepository;
use App\Service\CommandeService;
use App\Service\EmailService;
use App\Service\PaiementService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Stripe\Exception\SignatureVerificationException;

class PaiementServiceTest extends TestCase
{
    public function testLeModeTestRefuseUneCleLive(): void
    {
        $service = $this->creerService('sk_live_exemple', 'whsec_exemple', 'test');

        self::assertFalse($service->estConfigure());
    }

    public function testLeModeTestAccepteUneConfigurationTest(): void
    {
        $service = $this->creerService('sk_test_exemple', 'whsec_exemple', 'test');

        self::assertTrue($service->estConfigure());
    }

    public function testLeWebhookRefuseUneSignatureInvalide(): void
    {
        $service = $this->creerService('sk_test_exemple', 'whsec_exemple', 'test');
        $payload = '{"id":"evt_test","type":"unknown","data":{"object":{}}}';
        $signature = 't=' . time() . ',v1=signature-invalide';

        $this->expectException(SignatureVerificationException::class);
        $service->traiterWebhook($payload, $signature);
    }

    private function creerService(string $secret, string $webhook, string $mode): PaiementService
    {
        return new PaiementService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CommandeRepository::class),
            $this->createMock(CommandeService::class),
            $this->createMock(EmailService::class),
            $secret,
            $webhook,
            $mode,
            'http://localhost:5173',
        );
    }
}
