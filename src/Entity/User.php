<?php
namespace App\Entity;

use App\Database;
use PDO;

class User {
    private ?int $id_user = null;
    private string $role;
    private string $nom;
    private string $prenom;
    private string $mail;
    private string $mdp;

    public function getId(): ?int {
        return $this->id_user;
    }

    public function getRole(): string {
        return $this->role;
    }

    public function setRole(string $role): self {
        $this->role = $role;
        return $this;
    }

    public function getNom(): string {
        return $this->nom;
    }

    public function setNom(string $nom): self {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self {
        $this->prenom = $prenom;
        return $this;
    }

    public function getMail(): string {
        return $this->mail;
    }

    public function setMail(string $mail): self {
        $this->mail = $mail;
        return $this;
    }

    public function getMdp(): string {
        return $this->mdp;
    }

    public function setMdp(string $mdp): self {
        $this->mdp = password_hash($mdp, PASSWORD_DEFAULT);
        return $this;
    }

    public function setMdpRaw(string $mdp): self {
        $this->mdp = $mdp;
        return $this;
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->mdp);
    }

    public function save(): self {
        $pdo = Database::getConnection();
        
        if ($this->id_user === null) {
            $stmt = $pdo->prepare("INSERT INTO users (role, nom, prenom, mail, mdp) VALUES (:role, :nom, :prenom, :mail, :mdp) RETURNING id_user");
            $stmt->execute([
                'role' => $this->role,
                'nom' => $this->nom,
                'prenom' => $this->prenom,
                'mail' => $this->mail,
                'mdp' => $this->mdp
            ]);
            $this->id_user = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = :role, nom = :nom, prenom = :prenom, mail = :mail, mdp = :mdp WHERE id_user = :id");
            $stmt->execute([
                'role' => $this->role,
                'nom' => $this->nom,
                'prenom' => $this->prenom,
                'mail' => $this->mail,
                'mdp' => $this->mdp,
                'id' => $this->id_user
            ]);
        }
        
        return $this;
    }

    public function delete(): bool {
        if ($this->id_user === null) {
            return false;
        }
        
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = :id");
        return $stmt->execute(['id' => $this->id_user]);
    }

    public static function find(int $id): ?self {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }

    public static function findByMail(string $mail): ?self {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE mail = :mail");
        $stmt->execute(['mail' => $mail]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }

    public static function findAll(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM users ORDER BY id_user");
        $users = [];
        
        while ($data = $stmt->fetch()) {
            $users[] = self::hydrate($data);
        }
        
        return $users;
    }

    private static function hydrate(array $data): self {
        $user = new self();
        $user->id_user = $data['id_user'];
        $user->role = $data['role'];
        $user->nom = $data['nom'];
        $user->prenom = $data['prenom'];
        $user->mail = $data['mail'];
        $user->mdp = $data['mdp'];
        return $user;
    }

    public function getEmprunts(): array {
        return Emprunt::findByUser($this->id_user);
    }

    public function toArray(): array {
        return [
            'id_user' => $this->id_user,
            'role' => $this->role,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'mail' => $this->mail
        ];
    }
}
