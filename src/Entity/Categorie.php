<?php
namespace App\Entity;

use App\Database;
use PDO;

class Categorie {
    private ?int $id_categorie = null;
    private string $nom;

    public function getId(): ?int {
        return $this->id_categorie;
    }

    public function getNom(): string {
        return $this->nom;
    }

    public function setNom(string $nom): self {
        $this->nom = $nom;
        return $this;
    }

    public function save(): self {
        $pdo = Database::getConnection();
        
        if ($this->id_categorie === null) {
            $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (:nom) RETURNING id_categorie");
            $stmt->execute(['nom' => $this->nom]);
            $this->id_categorie = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET nom = :nom WHERE id_categorie = :id");
            $stmt->execute([
                'nom' => $this->nom,
                'id' => $this->id_categorie
            ]);
        }
        
        return $this;
    }

    public function delete(): bool {
        if ($this->id_categorie === null) {
            return false;
        }
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id_categorie = :id");
        return $stmt->execute(['id' => $this->id_categorie]);
    }

    public static function find(int $id): ?self {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id_categorie = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }

    public static function findAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
        $categories = [];
        
        while ($data = $stmt->fetch()) {
            $categories[] = self::hydrate($data);
        }
        
        return $categories;
    }

    private static function hydrate(array $data): self {
        $categorie = new self();
        $categorie->id_categorie = $data['id_categorie'];
        $categorie->nom = $data['nom'];
        return $categorie;
    }

    public function getProduits(): array {
        return Produit::findByCategorie($this->id_categorie);
    }

    public function toArray(): array {
        return [
            'id_categorie' => $this->id_categorie,
            'nom' => $this->nom
        ];
    }
}
