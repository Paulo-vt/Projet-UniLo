<?php
namespace App\Controller;

use App\Entity\Categorie;

class CategorieController extends AbstractController {

    public function index(): void {
        $categories = Categorie::findAll();
        $this->json(array_map(fn($c) => $c->toArray(), $categories));
    }

    public function show(int $id): void {
        $categorie = Categorie::find($id);
        
        if (!$categorie) {
            $this->notFound('Catégorie non trouvée');
        }

        $this->json($categorie->toArray());
    }

    public function store(): void {
        $this->requireAdmin();
        
        $data = $this->getJsonBody();

        if (empty($data['nom'])) {
            $this->badRequest('Le nom est requis');
        }

        $categorie = (new Categorie())
            ->setNom($data['nom'])
            ->save();

        $this->created($categorie->toArray());
    }

    public function update(int $id): void {
        $this->requireAdmin();
        
        $categorie = Categorie::find($id);
        
        if (!$categorie) {
            $this->notFound('Catégorie non trouvée');
        }

        $data = $this->getJsonBody();

        if (!empty($data['nom'])) {
            $categorie->setNom($data['nom']);
        }

        $categorie->save();
        $this->json($categorie->toArray());
    }

    public function destroy(int $id): void {
        $this->requireAdmin();
        
        $categorie = Categorie::find($id);
        
        if (!$categorie) {
            $this->notFound('Catégorie non trouvée');
        }

        // Vérifier s'il y a des produits liés
        $produits = $categorie->getProduits();
        if (!empty($produits)) {
            $this->badRequest('Impossible de supprimer : des produits sont liés à cette catégorie');
        }

        $categorie->delete();
        $this->noContent();
    }

    public function produits(int $id): void {
        $categorie = Categorie::find($id);
        
        if (!$categorie) {
            $this->notFound('Catégorie non trouvée');
        }

        $produits = $categorie->getProduits();
        $this->json(array_map(fn($p) => $p->toArray(), $produits));
    }
}
