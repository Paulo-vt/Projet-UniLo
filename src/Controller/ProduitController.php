<?php
namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Categorie;

class ProduitController extends AbstractController {

    public function index(): void {
        $produits = Produit::findAll();
        $this->json(array_map(fn($p) => $p->toArray(), $produits));
    }

    public function available(): void {
        $produits = Produit::findAvailable();
        $this->json(array_map(fn($p) => $p->toArray(), $produits));
    }

    public function show(int $id): void {
        $produit = Produit::find($id);
        
        if (!$produit) {
            $this->notFound('Produit non trouvé');
        }

        $data = $produit->toArray();
        $categorie = $produit->getCategorie();
        if ($categorie) {
            $data['categorie'] = $categorie->toArray();
        }

        $this->json($data);
    }

    public function store(): void {
        $this->requireAdmin();
        
        $data = $this->getJsonBody();

        $required = ['nom', 'caution', 'quantite', 'description', 'image', 'id_categorie'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $this->badRequest("Le champ '$field' est requis");
            }
        }

        // Vérifier que la catégorie existe
        if (!Categorie::find($data['id_categorie'])) {
            $this->badRequest('Catégorie invalide');
        }

        $produit = (new Produit())
            ->setNom($data['nom'])
            ->setCaution((float) $data['caution'])
            ->setQuantite((int) $data['quantite'])
            ->setStatut($data['statut'] ?? true)
            ->setDescription($data['description'])
            ->setImage($data['image'])
            ->setIdCategorie((int) $data['id_categorie'])
            ->save();

        $this->created($produit->toArray());
    }

    public function update(int $id): void {
        $this->requireAdmin();
        
        $produit = Produit::find($id);
        
        if (!$produit) {
            $this->notFound('Produit non trouvé');
        }

        $data = $this->getJsonBody();

        if (isset($data['nom'])) {
            $produit->setNom($data['nom']);
        }
        if (isset($data['caution'])) {
            $produit->setCaution((float) $data['caution']);
        }
        if (isset($data['quantite'])) {
            $produit->setQuantite((int) $data['quantite']);
        }
        if (isset($data['statut'])) {
            $produit->setStatut((bool) $data['statut']);
        }
        if (isset($data['description'])) {
            $produit->setDescription($data['description']);
        }
        if (isset($data['image'])) {
            $produit->setImage($data['image']);
        }
        if (isset($data['id_categorie'])) {
            if (!Categorie::find($data['id_categorie'])) {
                $this->badRequest('Catégorie invalide');
            }
            $produit->setIdCategorie((int) $data['id_categorie']);
        }

        $produit->save();
        $this->json($produit->toArray());
    }

    public function destroy(int $id): void {
        $this->requireAdmin();
        
        $produit = Produit::find($id);
        
        if (!$produit) {
            $this->notFound('Produit non trouvé');
        }

        // Vérifier s'il y a des emprunts en cours
        $emprunts = $produit->getEmprunts();
        if (!empty($emprunts)) {
            $this->badRequest('Impossible de supprimer : des emprunts sont liés à ce produit');
        }

        $produit->delete();
        $this->noContent();
    }

    public function emprunts(int $id): void {
        $produit = Produit::find($id);
        
        if (!$produit) {
            $this->notFound('Produit non trouvé');
        }

        $emprunts = $produit->getEmprunts();
        $this->json(array_map(fn($e) => $e->toArray(), $emprunts));
    }
}
