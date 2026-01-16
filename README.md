# Documentation du projet

## 1) Vue d’ensemble
Ce projet est une API PHP “simple” (ce n’est pas un Symfony standard), construite avec :

- `bramus/router` : routing HTTP (déclaration des routes et dispatch)
- `vlucas/phpdotenv` : chargement des variables d’environnement depuis `.env`
- `PDO` : accès base de données (PostgreSQL)

L’API expose des endpoints REST pour :

- **Auth** (connexion/déconnexion/session)
- **Users**
- **Categories**
- **Produits**
- **Emprunts**

## 2) Structure des dossiers

- `public/`
  - `index.php` : point d’entrée “site”, redirige vers `login.html`
  - `api.php` : point d’entrée de l’API (déclare les routes et exécute le routeur)
- `src/`
  - `Controller/` : contrôleurs HTTP (reçoivent la requête, valident, appellent les Entités, renvoient du JSON)
  - `Entity/` : modèle “Active Record” (CRUD via PDO + quelques relations)
  - `Database.php` : factory de connexion PDO vers PostgreSQL
- `.env` : configuration (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS…)

## 3) Point d’entrée API et routing

Le fichier **central** pour les routes est : `public/api.php`.

### 3.1) Initialisation
Dans `public/api.php` :

- Autoload Composer : `require __DIR__ . '/../vendor/autoload.php';`
- Chargement `.env` via Dotenv
- Gestion CORS (headers + réponse immédiate aux requêtes `OPTIONS`)
- Création du routeur : `$router = new Router();`

### 3.2) Déclaration des routes
Le routeur mappe une méthode HTTP + un chemin vers une fonction qui appelle un contrôleur.

Exemple (login) :

- Route : `POST /api/auth/login`
- Handler :
  - ` (new AuthController())->login();`

Exemple (route avec paramètre) :

- Route : `GET /api/users/(\d+)`
- Le `(\d+)` capture un identifiant numérique passé en argument `$id`.
- Handler :
  - `(new UserController())->show((int) $id);`

### 3.3) Route 404
Si aucune route ne match, `public/api.php` définit un `set404()` qui renvoie :

- `HTTP 404`
- JSON : `{"error":"Route not found"}`

## 4) Contrôleurs (`src/Controller`)

Les contrôleurs contiennent la logique “HTTP” :

- lecture du body JSON
- validation des champs
- contrôle d’accès (auth/admin)
- appel aux Entités (DB)
- formatage de la réponse JSON

### 4.1) `AbstractController`
Fichier : `src/Controller/AbstractController.php`

C’est la classe parent de tous les contrôleurs. Elle fournit les helpers :

- `json($data, $status=200)` : renvoie du JSON + status HTTP
- `created($data)` : renvoie `201`
- `noContent()` : renvoie `204`
- `badRequest($message)` : `400` avec `{ "error": "..." }`
- `unauthorized($message)` : `401`
- `forbidden($message)` : `403`
- `notFound($message)` : `404`
- `getJsonBody()` : lit `php://input` et parse en tableau

Et surtout les garde-fous d’accès :

- `requireAuth()`
  - démarre une session si nécessaire
  - vérifie `$_SESSION['user_id']`
  - sinon renvoie `401`
- `requireAdmin()`
  - appelle `requireAuth()`
  - vérifie `$_SESSION['user_role'] === 'admin'`
  - sinon renvoie `403`

### 4.2) `AuthController`
Fichier : `src/Controller/AuthController.php`

- `login()`
  - attend un JSON avec `mail` + `mdp`
  - cherche l’utilisateur via `User::findByMail()`
  - vérifie le mot de passe via `verifyPassword()`
  - crée la session :
    - `$_SESSION['user_id']`
    - `$_SESSION['user_role']`
  - renvoie un JSON `message + user`

- `logout()`
  - `session_destroy()`
  - renvoie `{ "message": "Déconnexion réussie" }`

- `me()`
  - vérifie qu’un `user_id` existe en session
  - renvoie l’utilisateur courant

### 4.3) `UserController`
Fichier : `src/Controller/UserController.php`

- `index()` : liste tous les users (protégé admin)
- `show($id)` : affiche 1 user
- `store()` : crée un user (protégé admin)
- `update($id)` : modifie un user (protégé admin)
- `destroy($id)` : supprime un user (protégé admin)
- `emprunts($id)` : renvoie les emprunts d’un user + champs calculés (`is_overdue`, `days_remaining`) + produit associé

### 4.4) `CategorieController`
Fichier : `src/Controller/CategorieController.php`

- CRUD classique
- `destroy()` refuse si des produits sont liés
- `produits($id)` : renvoie les produits de la catégorie

### 4.5) `ProduitController`
Fichier : `src/Controller/ProduitController.php`

- `index()` : liste
- `available()` : produits dispo (`statut = true` et `quantite > 0`)
- `show($id)` : renvoie le produit + la catégorie associée
- `store()/update()/destroy()` : protégés admin
- `destroy()` refuse si des emprunts sont liés
- `emprunts($id)` : emprunts du produit

### 4.6) `EmpruntController`
Fichier : `src/Controller/EmpruntController.php`

- `index()` : tous les emprunts
- `active()` : emprunts non retournés
- `overdue()` : emprunts en retard
- `store()` : crée un emprunt
  - vérifie user + produit
  - vérifie disponibilité produit
  - empêche un doublon “même user + même produit” tant que l’emprunt précédent n’est pas retourné
  - décrémente le stock du produit
- `update()` : permet de mettre à jour les dates + marquer `returned`
  - si passage à `returned=true`, réincrémente le stock
- `destroy()` : supprime un emprunt et réincrémente le stock

La méthode privée `formatEmprunt()` enrichit la réponse avec :

- `is_overdue`
- `days_remaining`
- `user` (détails)
- `produit` (détails)

## 5) Accès base de données (`src/Database.php`)

Fichier : `src/Database.php`

- `Database::getConnection()` construit un DSN PostgreSQL :
  - `pgsql:host=...;port=...;dbname=...`
- Retourne un `PDO` configuré avec :
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`

Les credentials viennent de `.env` (chargé dans `public/api.php`).

## 6) Entités (`src/Entity`) : modèle “Active Record”

Les classes dans `src/Entity` encapsulent :

- leurs champs (propriétés privées)
- des getters/setters
- des méthodes CRUD : `save()`, `delete()`, `find()`, `findAll()`, etc.
- des relations simples via d’autres Entités

### Exemple : `Produit`
Fichier : `src/Entity/Produit.php`

- `save()`
  - si `id_produit === null` : `INSERT ... RETURNING id_produit`
  - sinon : `UPDATE ... WHERE id_produit = :id`
- `find($id)` : `SELECT * FROM produits WHERE id_produit = :id`
- `findAvailable()` : `SELECT ... WHERE statut = true AND quantite > 0`
- Relations :
  - `getCategorie()` appelle `Categorie::find($this->id_categorie)`
  - `getEmprunts()` appelle `Emprunt::findByProduit($this->id_produit)`
- `toArray()` : format de sortie JSON

### Exemple : `Emprunt`
Fichier : `src/Entity/Emprunt.php`

- Stocke des `DateTime` côté PHP
- `isOverdue()` et `getDaysRemaining()` ajoutent de la logique métier
- Relations :
  - `getUser()` -> `User::find($this->id_user)`
  - `getProduit()` -> `Produit::find($this->id_produit)`

## 7) Authentification / Session

Le projet utilise les sessions PHP (pas de JWT).

- `AuthController::login()` crée la session
- `AbstractController::requireAuth()` bloque si pas connecté
- `AbstractController::requireAdmin()` bloque si pas admin

Donc, pour appeler les routes admin, il faut :

- d’abord faire `POST /api/auth/login`
- conserver le cookie de session côté client (navigateur / client HTTP)

## 8) Ajouter une nouvelle route (exemple)

1. Déclarer la route dans `public/api.php`.
2. Créer/compléter une méthode dans un contrôleur.
3. Ajouter la logique DB dans une Entité si nécessaire.

Exemple : ajouter `GET /api/ping`.

- Dans `public/api.php` :
  - `$router->get('/api/ping', function() { (new SomeController())->ping(); });`
- Dans le contrôleur :
  - `public function ping(): void { $this->json(['ok' => true]); }`

## 9) Codes de réponse HTTP utilisés

- `200` : succès (réponse JSON)
- `201` : créé (`created()`)
- `204` : supprimé sans contenu (`noContent()`)
- `400` : validation / erreur client (`badRequest()`)
- `401` : non connecté / identifiants invalides (`unauthorized()`)
- `403` : connecté mais rôle insuffisant (`forbidden()`)
- `404` : ressource absente (`notFound()` ou `set404()`)

---

Si tu veux, je peux aussi te faire :

- une **table récap de toutes les routes** (méthode + URL + description + droits)
- un schéma rapide “qui appelle qui” (API -> Controller -> Entity -> DB)
