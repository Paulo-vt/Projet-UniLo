<?php
namespace App\Entity;

use App\Database;
use PDO;

class Produit {
    private ?int $id_produit = null;
    private string $nom;
    private float $caution;
    private int $quantite;
    private bool $statut;
    private string $description;
    private string $image;
    private int $id_categorie;

    public function getId(): ?int {
        return $this->id_produit;
    }

    public function getNom(): string {
        return $this->nom;
    }

    public function setNom(string $nom): self {
        $this->nom = $nom;
        return $this;
    }

    public function getCaution(): float {
        return $this->caution;
    }

    public function setCaution(float $caution): self {
        $this->caution = $caution;
        return $this;
    }

    public function getQuantite(): int {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self {
        $this->quantite = $quantite;
        return $this;
    }

    public function getStatut(): bool {
        return $this->statut;
    }

    public function setStatut(bool $statut): self {
        $this->statut = $statut;
        return $this;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): self {
        $this->description = $description;
        return $this;
    }

    public function getImage(): string {
        return $this->image;
    }

    public function setImage(string $image): self {
        $this->image = $image;
        return $this;
    }

    public function getIdCategorie(): int {
        return $this->id_categorie;
    }

    public function setIdCategorie(int $id_categorie): self {
        $this->id_categorie = $id_categorie;
        return $this;
    }

    public function save(): self {
        $pdo = Database::getConnection();
        
        if ($this->id_produit === null) {
            $stmt = $pdo->prepare("INSERT INTO produits (nom, caution, quantite, statut, description, image, id_categorie) 
                VALUES (:nom, :caution, :quantite, :statut, :description, :image, :id_categorie) RETURNING id_produit");
            $stmt->execute([
                'nom' => $this->nom,
                'caution' => $this->caution,
                'quantite' => $this->quantite,
                'statut' => $this->statut ? 'true' : 'false',
                'description' => $this->description,
                'image' => $this->image,
                'id_categorie' => $this->id_categorie
            ]);
            $this->id_produit = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("UPDATE produits SET nom = :nom, caution = :caution, quantite = :quantite, 
                statut = :statut, description = :description, image = :image, id_categorie = :id_categorie 
                WHERE id_produit = :id");
            $stmt->execute([
                'nom' => $this->nom,
                'caution' => $this->caution,
                'quantite' => $this->quantite,
                'statut' => $this->statut ? 'true' : 'false',
                'description' => $this->description,
                'image' => $this->image,
                'id_categorie' => $this->id_categorie,
                'id' => $this->id_produit
            ]);
        }
        
        return $this;
    }

    public function delete(): bool {
        if ($this->id_produit === null) {
            return false;
        }
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM produits WHERE id_produit = :id");
        return $stmt->execute(['id' => $this->id_produit]);
    }

    public static function find(int $id): ?self {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_produit = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }

    public static function findAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM produits ORDER BY nom");
        $produits = [];
        
        while ($data = $stmt->fetch()) {
            $produits[] = self::hydrate($data);
        }
        
        return $produits;
    }

    public static function findByCategorie(int $id_categorie): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_categorie = :id_categorie ORDER BY nom");
        $stmt->execute(['id_categorie' => $id_categorie]);
        $produits = [];
        
        while ($data = $stmt->fetch()) {
            $produits[] = self::hydrate($data);
        }
        
        return $produits;
    }

    public static function findAvailable(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM produits WHERE statut = true AND quantite > 0 ORDER BY nom");
        $produits = [];
        
        while ($data = $stmt->fetch()) {
            $produits[] = self::hydrate($data);
        }
        
        return $produits;
    }

    private static function hydrate(array $data): self {
        $produit = new self();
        $produit->id_produit = $data['id_produit'];
        $produit->nom = $data['nom'];
        $produit->caution = (float) $data['caution'];
        $produit->quantite = (int) $data['quantite'];
        $produit->statut = (bool) $data['statut'];
        $produit->description = $data['description'];
        $produit->image = $data['image'];
        $produit->id_categorie = (int) $data['id_categorie'];
        return $produit;
    }

    public function getCategorie(): ?Categorie {
        return Categorie::find($this->id_categorie);
    }

    public function getEmprunts(): array {
        return Emprunt::findByProduit($this->id_produit);
    }

    public function toArray(): array {
        return [
            'id_produit' => $this->id_produit,
            'nom' => $this->nom,
            'caution' => $this->caution,
            'quantite' => $this->quantite,
            'statut' => $this->statut,
            'description' => $this->description,
            'image' => $this->image,
            'id_categorie' => $this->id_categorie
        ];
    }
}
