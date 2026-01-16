<?php
namespace App\Controller;

use App\Entity\Emprunt;
use App\Entity\User;
use App\Entity\Produit;
use DateTime;

class EmpruntController extends AbstractController {

    public function index(): void {
        $emprunts = Emprunt::findAll();
        $this->json(array_map(fn($e) => $this->formatEmprunt($e), $emprunts));
    }

    public function active(): void {
        $emprunts = Emprunt::findActive();
        $this->json(array_map(fn($e) => $this->formatEmprunt($e), $emprunts));
    }

    public function overdue(): void {
        $emprunts = Emprunt::findOverdue();
        $this->json(array_map(fn($e) => $this->formatEmprunt($e), $emprunts));
    }

    public function show(int $id): void {
        $emprunt = Emprunt::find($id);
        
        if (!$emprunt) {
            $this->notFound('Emprunt non trouvé');
        }

        $this->json($this->formatEmprunt($emprunt));
    }

    public function store(): void {
        $data = $this->getJsonBody();

        if (empty($data['id_user']) || empty($data['id_produit']) || empty($data['date_retour'])) {
            $this->badRequest('id_user, id_produit et date_retour sont requis');
        }

        // Vérifier que l'utilisateur existe
        $user = User::find($data['id_user']);
        if (!$user) {
            $this->badRequest('Utilisateur invalide');
        }

        // Vérifier que le produit existe et est disponible
        $produit = Produit::find($data['id_produit']);
        if (!$produit) {
            $this->badRequest('Produit invalide');
        }

        if (!$produit->getStatut() || $produit->getQuantite() <= 0) {
            $this->badRequest('Ce produit n\'est pas disponible');
        }

        // Vérifier la contrainte unique (user, produit) - seulement pour les emprunts non retournés
        $existingEmprunts = Emprunt::findByUser($data['id_user']);
        foreach ($existingEmprunts as $existing) {
            if ($existing->getIdProduit() === (int) $data['id_produit'] && !$existing->isRetourner()) {
                $this->badRequest('Cet utilisateur a déjà emprunté ce produit');
            }
        }

        $emprunt = (new Emprunt())
            ->setIdUser((int) $data['id_user'])
            ->setIdProduit((int) $data['id_produit'])
            ->setDateEmprunt(new DateTime($data['date_emprunt'] ?? 'now'))
            ->setDateRetour(new DateTime($data['date_retour']))
            ->save();

        // Décrémenter la quantité du produit
        $produit->setQuantite($produit->getQuantite() - 1)->save();

        $this->created($this->formatEmprunt($emprunt));
    }

    public function update(int $id): void {
        $emprunt = Emprunt::find($id);
        
        if (!$emprunt) {
            $this->notFound('Emprunt non trouvé');
        }

        $data = $this->getJsonBody();

        if (isset($data['date_emprunt'])) {
            $emprunt->setDateEmprunt(new DateTime($data['date_emprunt']));
        }
        if (isset($data['date_retour'])) {
            $emprunt->setDateRetour(new DateTime($data['date_retour']));
        }
        if (isset($data['returned'])) {
            $wasReturned = $emprunt->isRetourner();
            $emprunt->setRetourner((bool) $data['returned']);
            
            // Si on marque comme retourné et que ce n'était pas le cas avant, réincrémenter le stock
            if ($data['returned'] && !$wasReturned) {
                $produit = $emprunt->getProduit();
                if ($produit) {
                    $produit->setQuantite($produit->getQuantite() + 1)->save();
                }
            }
        }

        $emprunt->save();
        $this->json($this->formatEmprunt($emprunt));
    }

    public function destroy(int $id): void {
        $emprunt = Emprunt::find($id);
        
        if (!$emprunt) {
            $this->notFound('Emprunt non trouvé');
        }

        // Réincrémenter la quantité du produit
        $produit = $emprunt->getProduit();
        if ($produit) {
            $produit->setQuantite($produit->getQuantite() + 1)->save();
        }

        $emprunt->delete();
        $this->noContent();
    }

    private function formatEmprunt(Emprunt $emprunt): array {
        $data = $emprunt->toArray();
        $data['is_overdue'] = $emprunt->isOverdue();
        $data['days_remaining'] = $emprunt->getDaysRemaining();
        
        $user = $emprunt->getUser();
        if ($user) {
            $data['user'] = $user->toArray();
        }
        
        $produit = $emprunt->getProduit();
        if ($produit) {
            $data['produit'] = $produit->toArray();
        }
        
        return $data;
    }
}
