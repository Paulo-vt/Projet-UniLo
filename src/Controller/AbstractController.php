<?php
namespace App\Controller;

abstract class AbstractController {
    
    protected function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    protected function requireAuth(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['user_id'])) {
            $this->unauthorized('Vous devez être connecté');
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    protected function requireAdmin(): array {
        $session = $this->requireAuth();
        
        if ($session['role'] !== 'admin') {
            $this->forbidden('Accès réservé aux administrateurs');
        }
        
        return $session;
    }

    protected function getJsonBody(): array {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?? [];
    }

    protected function notFound(string $message = 'Resource not found'): void {
        $this->json(['error' => $message], 404);
    }

    protected function badRequest(string $message = 'Bad request'): void {
        $this->json(['error' => $message], 400);
    }

    protected function unauthorized(string $message = 'Unauthorized'): void {
        $this->json(['error' => $message], 401);
    }

    protected function forbidden(string $message = 'Forbidden'): void {
        $this->json(['error' => $message], 403);
    }

    protected function created(mixed $data): void {
        $this->json($data, 201);
    }

    protected function noContent(): void {
        http_response_code(204);
        exit;
    }
}
