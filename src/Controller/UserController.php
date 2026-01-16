<?php
namespace App\Controller;

use App\Entity\User;

class UserController extends AbstractController {

    public function index(): void {
        $this->requireAdmin();
        
        $users = User::findAll();
        $this->json(array_map(fn($u) => $u->toArray(), $users));
    }

    public function show(int $id): void {
        $user = User::find($id);
        
        if (!$user) {
            $this->notFound('Utilisateur non trouvé');
        }

        $this->json($user->toArray());
    }

    public function store(): void {
        $this->requireAdmin();
        
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

    public function update(int $id): void {
        $this->requireAdmin();
        
        $user = User::find($id);
        
        if (!$user) {
            $this->notFound('Utilisateur non trouvé');
        }

        $data = $this->getJsonBody();

        if (!empty($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (!empty($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        if (!empty($data['mail'])) {
            if ($data['mail'] !== $user->getMail() && User::findByMail($data['mail'])) {
                $this->badRequest('Cet email est déjà utilisé');
            }
            $user->setMail($data['mail']);
        }
        if (!empty($data['mdp'])) {
            $user->setMdp($data['mdp']);
        }
        if (!empty($data['role'])) {
            $user->setRole($data['role']);
        }

        $user->save();
        $this->json($user->toArray());
    }

    public function destroy(int $id): void {
        $this->requireAdmin();
        
        $user = User::find($id);
        
        if (!$user) {
            $this->notFound('Utilisateur non trouvé');
        }

        $user->delete();
        $this->noContent();
    }

    public function emprunts(int $id): void {
        $user = User::find($id);
        
        if (!$user) {
            $this->notFound('Utilisateur non trouvé');
        }

        $emprunts = $user->getEmprunts();
        $this->json(array_map(function($e) {
            $data = $e->toArray();
            $data['is_overdue'] = $e->isOverdue();
            $data['days_remaining'] = $e->getDaysRemaining();
            $produit = $e->getProduit();
            if ($produit) {
                $data['produit'] = $produit->toArray();
            }
            return $data;
        }, $emprunts));
    }
}
