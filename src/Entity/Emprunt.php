<?php
namespace App\Entity;

use App\Database;
use PDO;
use DateTime;

class Emprunt {
    private ?int $id_emprunt = null;
    private int $id_user;
    private int $id_produit;
    private DateTime $date_emprunt;
    private DateTime $date_retour;
    private bool $retourner = false;

    public function getId(): ?int {
        return $this->id_emprunt;
    }

    public function getIdUser(): int {
        return $this->id_user;
    }

    public function setIdUser(int $id_user): self {
        $this->id_user = $id_user;
        return $this;
    }

    public function getIdProduit(): int {
        return $this->id_produit;
    }

    public function setIdProduit(int $id_produit): self {
        $this->id_produit = $id_produit;
        return $this;
    }

    public function getDateEmprunt(): DateTime {
        return $this->date_emprunt;
    }

    public function setDateEmprunt(DateTime $date_emprunt): self {
        $this->date_emprunt = $date_emprunt;
        return $this;
    }

    public function getDateRetour(): DateTime {
        return $this->date_retour;
    }

    public function setDateRetour(DateTime $date_retour): self {
        $this->date_retour = $date_retour;
        return $this;
    }

    public function isRetourner(): bool {
        return $this->retourner;
    }

    public function setRetourner(bool $retourner): self {
        $this->retourner = $retourner;
        return $this;
    }

    public function save(): self {
        $pdo = Database::getConnection();
        
        if ($this->id_emprunt === null) {
            $stmt = $pdo->prepare("INSERT INTO emprunts (id_user, id_produit, date_emprunt, date_retour, retourner) 
                VALUES (:id_user, :id_produit, :date_emprunt, :date_retour, :retourner) RETURNING id_emprunt");
            $stmt->execute([
                'id_user' => $this->id_user,
                'id_produit' => $this->id_produit,
                'date_emprunt' => $this->date_emprunt->format('Y-m-d'),
                'date_retour' => $this->date_retour->format('Y-m-d'),
                'retourner' => $this->retourner ? 't' : 'f'
            ]);
            $this->id_emprunt = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("UPDATE emprunts SET id_user = :id_user, id_produit = :id_produit, 
                date_emprunt = :date_emprunt, date_retour = :date_retour, retourner = :retourner WHERE id_emprunt = :id");
            $stmt->execute([
                'id_user' => $this->id_user,
                'id_produit' => $this->id_produit,
                'date_emprunt' => $this->date_emprunt->format('Y-m-d'),
                'date_retour' => $this->date_retour->format('Y-m-d'),
                'retourner' => $this->retourner ? 't' : 'f',
                'id' => $this->id_emprunt
            ]);
        }
        
        return $this;
    }

    public function delete(): bool {
        if ($this->id_emprunt === null) {
            return false;
        }
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM emprunts WHERE id_emprunt = :id");
        return $stmt->execute(['id' => $this->id_emprunt]);
    }

    public static function find(int $id): ?self {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM emprunts WHERE id_emprunt = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }

    public static function findAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM emprunts ORDER BY date_emprunt DESC");
        $emprunts = [];
        
        while ($data = $stmt->fetch()) {
            $emprunts[] = self::hydrate($data);
        }
        
        return $emprunts;
    }

    public static function findByUser(int $id_user): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM emprunts WHERE id_user = :id_user ORDER BY date_emprunt DESC");
        $stmt->execute(['id_user' => $id_user]);
        $emprunts = [];
        
        while ($data = $stmt->fetch()) {
            $emprunts[] = self::hydrate($data);
        }
        
        return $emprunts;
    }

    public static function findByProduit(int $id_produit): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM emprunts WHERE id_produit = :id_produit ORDER BY date_emprunt DESC");
        $stmt->execute(['id_produit' => $id_produit]);
        $emprunts = [];
        
        while ($data = $stmt->fetch()) {
            $emprunts[] = self::hydrate($data);
        }
        
        return $emprunts;
    }

    public static function findActive(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM emprunts WHERE retourner = false ORDER BY date_retour");
        $emprunts = [];
        
        while ($data = $stmt->fetch()) {
            $emprunts[] = self::hydrate($data);
        }
        
        return $emprunts;
    }

    public static function findOverdue(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM emprunts WHERE date_retour < CURRENT_DATE AND retourner = false ORDER BY date_retour");
        $emprunts = [];
        
        while ($data = $stmt->fetch()) {
            $emprunts[] = self::hydrate($data);
        }
        
        return $emprunts;
    }

    private static function hydrate(array $data): self {
        $emprunt = new self();
        $emprunt->id_emprunt = $data['id_emprunt'];
        $emprunt->id_user = (int) $data['id_user'];
        $emprunt->id_produit = (int) $data['id_produit'];
        $emprunt->date_emprunt = new DateTime($data['date_emprunt']);
        $emprunt->date_retour = new DateTime($data['date_retour']);
        $emprunt->retourner = (bool) $data['retourner'];
        return $emprunt;
    }

    public function getUser(): ?User {
        return User::find($this->id_user);
    }

    public function getProduit(): ?Produit {
        return Produit::find($this->id_produit);
    }

    public function isOverdue(): bool {
        if ($this->retourner) return false;
        return $this->date_retour < new DateTime();
    }

    public function getDaysRemaining(): int {
        $now = new DateTime();
        $diff = $now->diff($this->date_retour);
        return $diff->invert ? -$diff->days : $diff->days;
    }

    public function toArray(): array {
        return [
            'id_emprunt' => $this->id_emprunt,
            'id_user' => $this->id_user,
            'id_produit' => $this->id_produit,
            'date_emprunt' => $this->date_emprunt->format('Y-m-d'),
            'date_retour' => $this->date_retour->format('Y-m-d'),
            'returned' => $this->retourner
        ];
    }
}
