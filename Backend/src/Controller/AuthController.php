<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\JsonSerializer;
use App\Service\EmailService;
use App\Service\MotDePasseService;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthController extends ApiController
{
    public function __construct(
        JsonSerializer $serializer,
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly MotDePasseService $motDePasseService,
        private readonly EmailService $emailService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $email = trim((string) ($data['email'] ?? ''));
        $motDePasse = (string) ($data['motDePasse'] ?? $data['password'] ?? '');
        $prenom = trim((string) ($data['prenom'] ?? ''));
        $nom = trim((string) ($data['nom'] ?? ''));

        if ($email === '' || $motDePasse === '' || $prenom === '' || $nom === '') {
            return $this->jsonErreur('Tous les champs sont obligatoires.');
        }

        if ($this->utilisateurRepository->findByEmail($email)) {
            return $this->jsonErreur('Cet email est déjà utilisé.', Response::HTTP_CONFLICT);
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($email);
        $utilisateur->setPrenom($prenom);
        $utilisateur->setNom($nom);
        $utilisateur->setMotDePasse($this->motDePasseService->hash($utilisateur, $motDePasse));
        $utilisateur->setJetonApi(bin2hex(random_bytes(32)));

        $this->em->persist($utilisateur);
        $this->em->flush();
        $this->emailService->envoyerBienvenue($utilisateur);

        return $this->json([
            'jeton' => $utilisateur->getJetonApi(),
            'utilisateur' => $this->serializer->normalize($utilisateur, ['utilisateur:lecture']),
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($data['email'] ?? ''));
        $motDePasse = (string) ($data['motDePasse'] ?? $data['password'] ?? '');

        $utilisateur = $this->utilisateurRepository->findByEmail($email);
        if (!$utilisateur || !$this->motDePasseService->verify($utilisateur, $motDePasse)) {
            return $this->jsonErreur('Identifiants incorrects.', Response::HTTP_UNAUTHORIZED);
        }

        $utilisateur->setJetonApi(bin2hex(random_bytes(32)));
        $this->em->flush();

        return $this->json([
            'jeton' => $utilisateur->getJetonApi(),
            'utilisateur' => $this->serializer->normalize($utilisateur, ['utilisateur:lecture']),
        ]);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $utilisateur = $this->getUtilisateurConnecte();
        if (!$utilisateur) {
            return $this->jsonErreur('Non authentifié.', Response::HTTP_UNAUTHORIZED);
        }

        return $this->jsonData($utilisateur, ['utilisateur:lecture', 'adresse:lecture']);
    }

    #[Route('/forgot-password', name: 'api_auth_forgot', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($data['email'] ?? ''));
        $utilisateur = $this->utilisateurRepository->findByEmail($email);

        if ($utilisateur) {
            $utilisateur->setJetonReinitialisation(bin2hex(random_bytes(32)));
            $utilisateur->setExpirationJetonReinitialisation(new \DateTimeImmutable('+1 hour'));
            $this->em->flush();
            $this->emailService->envoyerReinitialisationMotDePasse($utilisateur);
        }

        return $this->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
    }

    #[Route('/reset-password', name: 'api_auth_reset', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $jeton = (string) ($data['jeton'] ?? $data['token'] ?? '');
        $motDePasse = (string) ($data['motDePasse'] ?? $data['password'] ?? '');

        $utilisateur = $this->utilisateurRepository->findByJetonReinitialisation($jeton);
        if (!$utilisateur || !$utilisateur->getExpirationJetonReinitialisation() || $utilisateur->getExpirationJetonReinitialisation() < new \DateTimeImmutable()) {
            return $this->jsonErreur('Jeton invalide ou expiré.');
        }

        $utilisateur->setMotDePasse($this->motDePasseService->hash($utilisateur, $motDePasse));
        $utilisateur->setJetonReinitialisation(null);
        $utilisateur->setExpirationJetonReinitialisation(null);
        $this->em->flush();

        return $this->json(['message' => 'Mot de passe mis à jour.']);
    }
}
