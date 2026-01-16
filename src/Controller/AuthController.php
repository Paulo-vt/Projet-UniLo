<?php
namespace App\Controller;

use App\Entity\User;

class AuthController extends AbstractController {

    public function register(): void {
        $data = $this->getJsonBody();

        if (empty($data['nom']) || empty($data['prenom']) || empty($data['mail']) || empty($data['mdp'])) {
            $this->badRequest('Tous les champs sont requis');
        }

        if (User::findByMail($data['mail'])) {
            $this->badRequest('Cet email est déjà utilisé');
        }

        $user = (new User())
            ->setNom($data['nom'])
            ->setPrenom($data['prenom'])
            ->setMail($data['mail'])
            ->setMdp($data['mdp'])
            ->setRole($data['role'] ?? 'user')
            ->save();

        $this->created($user->toArray());
    }

    public function login(): void {
        $data = $this->getJsonBody();

        if (empty($data['mail']) || empty($data['mdp'])) {
            $this->badRequest('Email et mot de passe requis');
        }

        $user = User::findByMail($data['mail']);

        if (!$user || !$user->verifyPassword($data['mdp'])) {
            $this->unauthorized('Identifiants invalides');
        }

        session_start();
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_role'] = $user->getRole();

        $this->json([
            'message' => 'Connexion réussie',
            'user' => $user->toArray()
        ]);
    }

    public function logout(): void {
        session_start();
        session_destroy();
        $this->json(['message' => 'Déconnexion réussie']);
    }

    public function me(): void {
        session_start();
        
        if (empty($_SESSION['user_id'])) {
            $this->unauthorized('Non connecté');
        }

        $user = User::find($_SESSION['user_id']);
        
        if (!$user) {
            $this->unauthorized('Utilisateur non trouvé');
        }

        $this->json($user->toArray());
    }
}
