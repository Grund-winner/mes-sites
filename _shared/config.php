<?php
/**
 * FICHIER DE CONFIGURATION PARTAGÉ
 * 
 * Ce fichier contient les fonctions et configurations communes
 * à tous les sites e-commerce.
 * 
 * Pour utiliser ce fichier, ajoutez en haut de vos fichiers PHP:
 * require_once '../_shared/config.php';
 */

// ============================================================================
// CONFIGURATION GLOBALE
// ============================================================================

// Thèmes disponibles pour tous les sites
$GLOBALS['AVAILABLE_THEMES'] = [
    'theme-1-rose' => ['name' => 'Rose Pink', 'color' => '#ec4899', 'icon' => '🌸', 'desc' => 'Idéal pour cosmétiques et beauté'],
    'theme-2-bleu' => ['name' => 'Bleu Océan', 'color' => '#0ea5e9', 'icon' => '🌊', 'desc' => 'Parfait pour tech et téléphones'],
    'theme-3-vert' => ['name' => 'Vert Nature', 'color' => '#10b981', 'icon' => '🌿', 'desc' => 'Adapté pour restaurants et bio'],
    'theme-4-or' => ['name' => 'Or Luxe', 'color' => '#d4af37', 'icon' => '✨', 'desc' => 'Élégant pour montres et bijoux'],
    'theme-5-rouge' => ['name' => 'Rouge Passion', 'color' => '#dc2626', 'icon' => '❤️', 'desc' => 'Dynamique pour chaussures et sport'],
    'theme-6-violet' => ['name' => 'Violet Royal', 'color' => '#7c3aed', 'icon' => '💜', 'desc' => 'Royal pour bijoux et accessoires'],
    'theme-7-orange' => ['name' => 'Orange Énergie', 'color' => '#f97316', 'icon' => '☀️', 'desc' => 'Énergique pour électroménager'],
    'theme-8-noir' => ['name' => 'Noir Élégant', 'color' => '#1f2937', 'icon' => '🖤', 'desc' => 'Sophistiqué pour luxe masculin'],
    'theme-9-turquoise' => ['name' => 'Turquoise Tropical', 'color' => '#14b8a6', 'icon' => '🏝️', 'desc' => 'Exotique pour sacs et plage'],
    'theme-10-marron' => ['name' => 'Marron Cuir', 'color' => '#92400e', 'icon' => '🤎', 'desc' => 'Chaleureux pour cuir et accessoires'],
    'theme-11-neon' => ['name' => 'Néon Futuriste', 'color' => '#00ff88', 'icon' => '⚡', 'desc' => 'Moderne et vibrant pour sport'],
    'theme-12-minimal' => ['name' => 'Minimaliste', 'color' => '#64748b', 'icon' => '◽', 'desc' => 'Épuré et professionnel'],
];

// ============================================================================
// FONCTIONS DE GESTION DES DONNÉES
// ============================================================================

/**
 * Charge les données depuis un fichier JSON
 * @param string $dataFile Chemin vers le fichier de données
 * @return array Données chargées
 */
function loadSiteData($dataFile = 'data.json') {
    if (file_exists($dataFile)) {
        $json = file_get_contents($dataFile);
        $data = json_decode($json, true);
        if ($data !== null) {
            return $data;
        }
    }
    return getDefaultSiteData();
}

/**
 * Retourne les données par défaut pour un site e-commerce
 * @return array Données par défaut
 */
function getDefaultSiteData() {
    return [
        'site' => [
            'name' => 'Ma Boutique',
            'tagline' => 'Votre shopping en ligne',
            'description' => 'Découvrez notre sélection de produits de qualité.',
            'phone' => '22890000000',
            'email' => 'contact@maboutique.tg',
            'address' => 'Lomé, Togo',
            'logo' => 'logo.png',
            'stats' => [
                'products' => '100+',
                'rating' => '4.8',
                'delivery' => '24h'
            ]
        ],
        'categories' => [
            ['id' => 'tous', 'name' => 'Tous', 'icon' => '✨', 'count' => 0],
        ],
        'genders' => ['Tous', 'Femme', 'Homme', 'Mixte'],
        'products' => [],
        'featured' => [],
        'customers' => [],
        'orders' => [],
        'admin' => [
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT)
        ],
        'settings' => [
            'currency' => 'FCFA',
            'theme' => 'theme-1-rose',
            'whatsapp_message' => 'Bonjour, je souhaite commander :'
        ]
    ];
}

/**
 * Sauvegarde les données dans un fichier JSON
 * @param array $data Données à sauvegarder
 * @param string $dataFile Chemin vers le fichier
 * @return bool Succès de l'opération
 */
function saveSiteData($data, $dataFile = 'data.json') {
    // Recalculer les compteurs de catégories
    if (isset($data['categories']) && isset($data['products'])) {
        foreach ($data['categories'] as &$category) {
            if ($category['id'] === 'tous') {
                $category['count'] = count($data['products']);
            } else {
                $count = 0;
                foreach ($data['products'] as $product) {
                    if (isset($product['category']) && $product['category'] === $category['id']) {
                        $count++;
                    }
                }
                $category['count'] = $count;
            }
        }
    }
    return file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// ============================================================================
// FONCTIONS D'AUTHENTIFICATION
// ============================================================================

/**
 * Vérifie si l'admin est connecté
 * @return bool
 */
function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        return false;
    }
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > 3600)) {
        session_destroy();
        return false;
    }
    $_SESSION['admin_last_activity'] = time();
    return true;
}

/**
 * Connecte l'admin
 * @param string $username
 * @param string $password
 * @param array $adminConfig Configuration admin (username, password hash)
 * @return bool
 */
function loginAdmin($username, $password, $adminConfig) {
    if ($username === $adminConfig['username'] && password_verify($password, $adminConfig['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_last_activity'] = time();
        return true;
    }
    return false;
}

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

/**
 * Formate un prix
 * @param int $price Prix en unités
 * @param string $currency Devise
 * @return string Prix formaté
 */
function formatPrice($price, $currency = 'FCFA') {
    return number_format($price, 0, ',', ' ') . ' ' . $currency;
}

/**
 * Génère un badge de statut HTML
 * @param string $status Statut de la commande
 * @return string HTML du badge
 */
function getStatusBadge($status) {
    $badges = [
        'en_attente' => '<span class="badge badge-warning">En attente</span>',
        'en_cours' => '<span class="badge badge-info">En cours</span>',
        'terminee' => '<span class="badge badge-success">Terminée</span>',
        'annulee' => '<span class="badge badge-danger">Annulée</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-default">' . htmlspecialchars($status) . '</span>';
}

/**
 * Nettoie une chaîne pour les URLs
 * @param string $string
 * @return string
 */
function slugify($string) {
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $string), '-'));
}

/**
 * Génère un message WhatsApp
 * @param array $items Articles commandés
 * @param int $total Total de la commande
 * @param array $customer Informations client
 * @param array $siteSettings Paramètres du site
 * @return string Message formaté
 */
function generateWhatsAppMessage($items, $total, $customer, $siteSettings) {
    $message = $siteSettings['whatsapp_message'] ?? 'Bonjour, je souhaite commander :' . "\n\n";
    $message .= "*Client:* " . $customer['firstname'] . ' ' . $customer['lastname'] . "\n";
    $message .= "*Téléphone:* " . $customer['phone'] . "\n";
    $message .= "*Quartier:* " . $customer['quartier'] . "\n\n";
    $message .= "*Articles commandés:*\n";
    foreach ($items as $item) {
        $message .= "• " . $item['name'] . " (" . ($item['brand'] ?? '') . ")\n";
        $message .= "  " . $item['quantity'] . " x " . formatPrice($item['price'], $siteSettings['currency'] ?? 'FCFA') . "\n\n";
    }
    $message .= "*Total: " . formatPrice($total, $siteSettings['currency'] ?? 'FCFA') . "*";
    return $message;
}

// ============================================================================
// FONCTIONS D'UPLOAD
// ============================================================================

/**
 * Upload une image
 * @param array $file Données $_FILES
 * @param string $targetDir Dossier cible
 * @param int $maxSize Taille max en bytes (défaut 5MB)
 * @return array ['success' => bool, 'message' => string, 'path' => string]
 */
function uploadImage($file, $targetDir = 'uploads/', $maxSize = 5242880) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Aucun fichier ou erreur d\'upload'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Le fichier est trop volumineux (max ' . ($maxSize / 1048576) . 'MB)'];
    }
    
    // Créer le dossier si nécessaire
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $targetPath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'message' => 'Image uploadée avec succès', 'path' => $targetPath];
    }
    
    return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
}

/**
 * Récupère les images d'un dossier
 * @param string $dir Dossier à scanner
 * @return array Liste des images
 */
function getGalleryImages($dir = 'uploads/gallery/') {
    $images = [];
    if (is_dir($dir)) {
        $files = glob($dir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            $images[] = [
                'name' => basename($file),
                'url' => $dir . basename($file)
            ];
        }
    }
    return $images;
}

// ============================================================================
// FONCTIONS DE SÉCURITÉ
// ============================================================================

/**
 * Hash un mot de passe
 * @param string $password
 * @return string Hash du mot de passe
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Vérifie un mot de passe
 * @param string $password Mot de passe en clair
 * @param string $hash Hash stocké
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Nettoie les entrées utilisateur
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
