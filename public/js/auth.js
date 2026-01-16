/**
 * Module d'authentification et contrôle d'accès
 */
const Auth = {
    user: null,
    
    /**
     * Vérifie si l'utilisateur est connecté
     */
    async check() {
        try {
            const response = await fetch('/challg/public/api/auth/me');
            if (response.ok) {
                this.user = await response.json();
                return true;
            }
            return false;
        } catch (err) {
            console.error('Erreur auth:', err);
            return false;
        }
    },

    /**
     * Récupère l'utilisateur connecté
     */
    getUser() {
        return this.user;
    },

    /**
     * Vérifie si l'utilisateur est admin
     */
    isAdmin() {
        return this.user && this.user.role === 'admin';
    },

    /**
     * Vérifie si l'utilisateur est un élève/user standard
     */
    isStudent() {
        return this.user && this.user.role === 'user';
    },

    /**
     * Protège une page - redirige si non connecté
     */
    async requireAuth(redirectTo = 'login.html') {
        const isLogged = await this.check();
        if (!isLogged) {
            window.location.href = '/challg/public/' + redirectTo;
            return false;
        }
        return true;
    },

    /**
     * Protège une page admin - redirige si non admin
     */
    async requireAdmin(redirectTo = 'user/accueil.html') {
        const isLogged = await this.check();
        if (!isLogged) {
            window.location.href = '/challg/public/login.html';
            return false;
        }
        if (!this.isAdmin()) {
            window.location.href = '/challg/public/' + redirectTo;
            return false;
        }
        return true;
    },

    /**
     * Protège une page élève - redirige si admin (vers espace admin)
     */
    async requireStudent(redirectToAdmin = 'admin/accueil.html') {
        const isLogged = await this.check();
        if (!isLogged) {
            window.location.href = '/challg/public/login.html';
            return false;
        }
        // Si admin, rediriger vers l'espace admin
        if (this.isAdmin()) {
            window.location.href = '/challg/public/' + redirectToAdmin;
            return false;
        }
        return true;
    },

    /**
     * Déconnexion
     */
    async logout() {
        try {
            await fetch('/challg/public/api/auth/logout', { method: 'POST' });
            window.location.href = '/challg/public/login.html';
        } catch (err) {
            console.error('Erreur logout:', err);
        }
    },

    /**
     * Redirige selon le rôle après connexion
     */
    redirectByRole() {
        if (this.isAdmin()) {
            window.location.href = '/challg/public/admin/accueil.html';
        } else {
            window.location.href = '/challg/public/user/accueil.html';
        }
    }
};
