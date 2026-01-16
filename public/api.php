<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Bramus\Router\Router;
use App\Controller\AuthController;
use App\Controller\UserController;
use App\Controller\CategorieController;
use App\Controller\ProduitController;
use App\Controller\EmpruntController;

// Charger les variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialiser le routeur
$router = new Router();

// Routes d'authentification
$router->post('/api/auth/login', function() {
    (new AuthController())->login();
});

$router->post('/api/auth/logout', function() {
    (new AuthController())->logout();
});

$router->get('/api/auth/me', function() {
    (new AuthController())->me();
});

// Routes Users
$router->get('/api/users', function() {
    (new UserController())->index();
});

$router->get('/api/users/(\d+)', function($id) {
    (new UserController())->show((int) $id);
});

$router->post('/api/users', function() {
    (new UserController())->store();
});

$router->put('/api/users/(\d+)', function($id) {
    (new UserController())->update((int) $id);
});

$router->delete('/api/users/(\d+)', function($id) {
    (new UserController())->destroy((int) $id);
});

$router->get('/api/users/(\d+)/emprunts', function($id) {
    (new UserController())->emprunts((int) $id);
});

// Routes Categories
$router->get('/api/categories', function() {
    (new CategorieController())->index();
});

$router->get('/api/categories/(\d+)', function($id) {
    (new CategorieController())->show((int) $id);
});

$router->post('/api/categories', function() {
    (new CategorieController())->store();
});

$router->put('/api/categories/(\d+)', function($id) {
    (new CategorieController())->update((int) $id);
});

$router->delete('/api/categories/(\d+)', function($id) {
    (new CategorieController())->destroy((int) $id);
});

$router->get('/api/categories/(\d+)/produits', function($id) {
    (new CategorieController())->produits((int) $id);
});

// Routes Produits
$router->get('/api/produits', function() {
    (new ProduitController())->index();
});

$router->get('/api/produits/available', function() {
    (new ProduitController())->available();
});

$router->get('/api/produits/(\d+)', function($id) {
    (new ProduitController())->show((int) $id);
});

$router->post('/api/produits', function() {
    (new ProduitController())->store();
});

$router->put('/api/produits/(\d+)', function($id) {
    (new ProduitController())->update((int) $id);
});

$router->delete('/api/produits/(\d+)', function($id) {
    (new ProduitController())->destroy((int) $id);
});

$router->get('/api/produits/(\d+)/emprunts', function($id) {
    (new ProduitController())->emprunts((int) $id);
});

// Routes Emprunts
$router->get('/api/emprunts', function() {
    (new EmpruntController())->index();
});

$router->get('/api/emprunts/active', function() {
    (new EmpruntController())->active();
});

$router->get('/api/emprunts/overdue', function() {
    (new EmpruntController())->overdue();
});

$router->get('/api/emprunts/(\d+)', function($id) {
    (new EmpruntController())->show((int) $id);
});

$router->post('/api/emprunts', function() {
    (new EmpruntController())->store();
});

$router->put('/api/emprunts/(\d+)', function($id) {
    (new EmpruntController())->update((int) $id);
});

$router->delete('/api/emprunts/(\d+)', function($id) {
    (new EmpruntController())->destroy((int) $id);
});

// Route 404
$router->set404(function() {
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
});

// Exécuter le routeur
$router->run();
