<?php
/**
 * E-COMMERCE MODULAIRE - ADMIN.PHP V3.0 AMÉLIORÉ
 * 
 * Panneau d'administration complet avec:
 * - Gestion des produits (CRUD) avec upload d'images local
 * - Changement de mot de passe admin SAUVEGARDÉ dans data.json
 * - Reset des statistiques avec confirmation sécurisée
 * - Galerie d'images locale
 * - Changement de thème en temps réel (12 thèmes)
 * 
 * @version 3.0 IMPROVED
 * @author Assistant IA
 */

session_start();

// ============================================================================
// CONFIGURATION
// ============================================================================

// Fichier de données JSON
define('DATA_FILE', 'data.json');

// Dossier des uploads
define('UPLOADS_DIR', 'uploads/');
define('GALLERY_DIR', UPLOADS_DIR . 'gallery/');
define('PRODUCTS_DIR', UPLOADS_DIR . 'products/');

// Créer les dossiers s'ils n'existent pas
if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
if (!is_dir(GALLERY_DIR)) mkdir(GALLERY_DIR, 0755, true);
if (!is_dir(PRODUCTS_DIR)) mkdir(PRODUCTS_DIR, 0755, true);

// Thèmes disponibles (10 originaux + 2 nouveaux)
$availableThemes = [
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

function loadData() {
    if (file_exists(DATA_FILE)) {
        $json = file_get_contents(DATA_FILE);
        $data = json_decode($json, true);
        if ($data !== null) {
            return $data;
        }
    }
    return getDefaultData();
}

function getDefaultData() {
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

function saveData($data) {
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
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// ============================================================================
// CHARGEMENT INITIAL DES DONNÉES
// ============================================================================

$data = loadData();

// Initialiser les structures si elles n'existent pas
if (!isset($data['customers'])) $data['customers'] = [];
if (!isset($data['orders'])) $data['orders'] = [];
if (!isset($data['products'])) $data['products'] = [];
if (!isset($data['categories'])) $data['categories'] = [];
if (!isset($data['site'])) $data['site'] = ['name' => 'Ma Boutique'];
if (!isset($data['admin'])) {
    $data['admin'] = [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT)
    ];
    saveData($data);
}
if (!isset($data['settings']['theme'])) $data['settings']['theme'] = 'theme-1-rose';

// ============================================================================
// VÉRIFICATION DE CONNEXION
// ============================================================================

function isLoggedIn() {
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

// Rediriger si non connecté
$showLogin = false;
if (!isLoggedIn() && !isset($_POST['login'])) {
    $showLogin = true;
}

// ============================================================================
// TRAITEMENT DU LOGIN
// ============================================================================

$error = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $data['admin']['username'] && password_verify($password, $data['admin']['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_last_activity'] = time();
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Identifiants incorrects';
        $showLogin = true;
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ============================================================================
// TRAITEMENT DES ACTIONS
// ============================================================================

$message = '';
$messageType = '';
$page = $_GET['page'] ?? 'dashboard';

// ============================================================================
// 1. CHANGEMENT DE MOT DE PASSE (CORRIGÉ - SAUVEGARDE DANS data.json)
// ============================================================================

if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Vérifier l'ancien mot de passe
    if (!password_verify($currentPassword, $data['admin']['password'])) {
        $message = 'Mot de passe actuel incorrect';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Les nouveaux mots de passe ne correspondent pas';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Le mot de passe doit contenir au moins 6 caractères';
        $messageType = 'error';
    } else {
        // Sauvegarder le nouveau mot de passe hashé dans data.json
        $data['admin']['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        saveData($data);
        $message = 'Mot de passe modifié avec succès !';
        $messageType = 'success';
    }
}

// ============================================================================
// 2. RESET DES STATISTIQUES (NOUVEAU)
// ============================================================================

if (isset($_POST['reset_stats'])) {
    $confirmUsername = $_POST['confirm_username'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $resetType = $_POST['reset_type'] ?? '';
    
    // Vérifier les identifiants
    if ($confirmUsername !== $data['admin']['username'] || !password_verify($confirmPassword, $data['admin']['password'])) {
        $message = 'Identifiants incorrects. Action annulée.';
        $messageType = 'error';
    } else {
        switch ($resetType) {
            case 'orders':
                $data['orders'] = [];
                $message = 'Toutes les commandes ont été supprimées';
                break;
            case 'customers':
                $data['customers'] = [];
                $data['orders'] = []; // Supprimer aussi les commandes
                $message = 'Tous les clients et leurs commandes ont été supprimés';
                break;
            case 'products':
                $data['products'] = [];
                $data['orders'] = [];
                $message = 'Tous les produits et commandes ont été supprimés';
                break;
            case 'all':
                $data['orders'] = [];
                $data['customers'] = [];
                $data['products'] = [];
                $message = 'Toutes les données ont été réinitialisées';
                break;
            default:
                $message = 'Type de réinitialisation non valide';
                $messageType = 'error';
        }
        if ($messageType !== 'error') {
            saveData($data);
            $messageType = 'success';
        }
    }
}

// ============================================================================
// 3. UPLOAD D'IMAGES PRODUITS (AMÉLIORÉ)
// ============================================================================

// Upload d'image pour la galerie
if (isset($_POST['upload_image'])) {
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image_file']['type'], $allowedTypes)) {
            $message = 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.';
            $messageType = 'error';
        } elseif ($_FILES['image_file']['size'] > $maxSize) {
            $message = 'Le fichier est trop volumineux (max 5MB)';
            $messageType = 'error';
        } else {
            $extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $targetPath = GALLERY_DIR . $fileName;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                $message = 'Image uploadée avec succès !';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de l\'upload. Vérifiez les permissions du dossier.';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Aucun fichier sélectionné ou erreur d\'upload';
        $messageType = 'error';
    }
}

// Upload d'image produit direct
if (isset($_FILES['product_image_upload']) && $_FILES['product_image_upload']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;
    
    if (in_array($_FILES['product_image_upload']['type'], $allowedTypes) && $_FILES['product_image_upload']['size'] <= $maxSize) {
        $extension = pathinfo($_FILES['product_image_upload']['name'], PATHINFO_EXTENSION);
        $fileName = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $targetPath = PRODUCTS_DIR . $fileName;
        
        if (move_uploaded_file($_FILES['product_image_upload']['tmp_name'], $targetPath)) {
            // Retourner l'URL pour le formulaire
            $uploadedImageUrl = PRODUCTS_DIR . $fileName;
        }
    }
}

// Supprimer une image de la galerie
if (isset($_GET['delete_gallery_image'])) {
    $imageName = basename($_GET['delete_gallery_image']);
    $imagePath = GALLERY_DIR . $imageName;
    if (file_exists($imagePath)) {
        unlink($imagePath);
        $message = 'Image supprimée';
        $messageType = 'success';
    }
}

// ============================================================================
// GESTION DES PRODUITS
// ============================================================================

// Ajouter un produit
if (isset($_POST['add_product'])) {
    // Gérer l'upload d'image principale
    $mainImage = $_POST['product_image'] ?? '';
    
    // Si un fichier a été uploadé, l'utiliser
    if (isset($_FILES['product_main_image']) && $_FILES['product_main_image']['error'] === UPLOAD_ERR_OK) {
        $extension = pathinfo($_FILES['product_main_image']['name'], PATHINFO_EXTENSION);
        $fileName = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $targetPath = PRODUCTS_DIR . $fileName;
        
        if (move_uploaded_file($_FILES['product_main_image']['tmp_name'], $targetPath)) {
            $mainImage = PRODUCTS_DIR . $fileName;
        }
    }
    
    $newProduct = [
        'id' => time(),
        'name' => $_POST['product_name'],
        'brand' => $_POST['product_brand'] ?? '',
        'category' => $_POST['product_category'],
        'gender' => $_POST['product_gender'] ?? 'Mixte',
        'price' => intval($_POST['product_price']),
        'old_price' => $_POST['product_old_price'] ? intval($_POST['product_old_price']) : null,
        'stock' => intval($_POST['product_stock'] ?? 0),
        'badge' => $_POST['product_badge'] ?? '',
        'rating' => intval($_POST['product_rating'] ?? 0),
        'image' => $mainImage,
        'images' => [],
        'description' => $_POST['product_description'] ?? ''
    ];
    
    // Ajouter les images supplémentaires
    for ($i = 1; $i <= 5; $i++) {
        if (!empty($_POST['product_image_' . $i])) {
            $newProduct['images'][] = $_POST['product_image_' . $i];
        }
    }
    
    $data['products'][] = $newProduct;
    saveData($data);
    $message = 'Produit ajouté avec succès';
    $messageType = 'success';
}

// Modifier un produit
if (isset($_POST['edit_product'])) {
    $productId = intval($_POST['product_id']);
    foreach ($data['products'] as &$product) {
        if ($product['id'] === $productId) {
            // Gérer l'upload de nouvelle image principale
            $mainImage = $_POST['product_image'];
            
            if (isset($_FILES['product_main_image']) && $_FILES['product_main_image']['error'] === UPLOAD_ERR_OK) {
                $extension = pathinfo($_FILES['product_main_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                $targetPath = PRODUCTS_DIR . $fileName;
                
                if (move_uploaded_file($_FILES['product_main_image']['tmp_name'], $targetPath)) {
                    $mainImage = PRODUCTS_DIR . $fileName;
                }
            }
            
            $product['name'] = $_POST['product_name'];
            $product['brand'] = $_POST['product_brand'] ?? '';
            $product['category'] = $_POST['product_category'];
            $product['gender'] = $_POST['product_gender'] ?? 'Mixte';
            $product['price'] = intval($_POST['product_price']);
            $product['old_price'] = $_POST['product_old_price'] ? intval($_POST['product_old_price']) : null;
            $product['stock'] = intval($_POST['product_stock'] ?? 0);
            $product['badge'] = $_POST['product_badge'] ?? '';
            $product['rating'] = intval($_POST['product_rating'] ?? 0);
            $product['image'] = $mainImage;
            $product['description'] = $_POST['product_description'] ?? '';
            
            $product['images'] = [];
            for ($i = 1; $i <= 5; $i++) {
                if (!empty($_POST['product_image_' . $i])) {
                    $product['images'][] = $_POST['product_image_' . $i];
                }
            }
            break;
        }
    }
    saveData($data);
    $message = 'Produit modifié avec succès';
    $messageType = 'success';
}

// Supprimer un produit
if (isset($_GET['delete_product'])) {
    $productId = intval($_GET['delete_product']);
    $data['products'] = array_filter($data['products'], fn($p) => $p['id'] !== $productId);
    $data['products'] = array_values($data['products']);
    saveData($data);
    $message = 'Produit supprimé';
    $messageType = 'success';
}

// ============================================================================
// GESTION DES CATÉGORIES
// ============================================================================

if (isset($_POST['add_category'])) {
    $newCategory = [
        'id' => strtolower(str_replace(' ', '-', $_POST['category_name'])),
        'name' => $_POST['category_name'],
        'icon' => $_POST['category_icon'] ?? '📦',
        'count' => 0
    ];
    $data['categories'][] = $newCategory;
    saveData($data);
    $message = 'Catégorie ajoutée';
    $messageType = 'success';
}

if (isset($_GET['delete_category'])) {
    $categoryId = $_GET['delete_category'];
    $data['categories'] = array_filter($data['categories'], fn($c) => $c['id'] !== $categoryId);
    $data['categories'] = array_values($data['categories']);
    saveData($data);
    $message = 'Catégorie supprimée';
    $messageType = 'success';
}

// ============================================================================
// GESTION DES COMMANDES
// ============================================================================

if (isset($_POST['update_order_status'])) {
    $orderId = $_POST['order_id'];
    foreach ($data['orders'] as &$order) {
        if ($order['id'] === $orderId) {
            $order['status'] = $_POST['status'];
            $order['delivery_status'] = $_POST['delivery_status'];
            $order['delivery_name'] = $_POST['delivery_name'] ?? '';
            $order['delivery_phone'] = $_POST['delivery_phone'] ?? '';
            $order['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    saveData($data);
    $message = 'Statut de la commande mis à jour';
    $messageType = 'success';
}

// ============================================================================
// PARAMÈTRES DU SITE
// ============================================================================

if (isset($_POST['save_site_settings'])) {
    $data['site']['name'] = $_POST['site_name'];
    $data['site']['tagline'] = $_POST['site_tagline'];
    $data['site']['description'] = $_POST['site_description'];
    $data['site']['phone'] = $_POST['site_phone'];
    $data['site']['email'] = $_POST['site_email'];
    $data['site']['address'] = $_POST['site_address'];
    $data['site']['logo'] = $_POST['site_logo'];
    $data['site']['stats']['products'] = $_POST['stat_products'];
    $data['site']['stats']['rating'] = $_POST['stat_rating'];
    $data['site']['stats']['delivery'] = $_POST['stat_delivery'];
    saveData($data);
    $message = 'Paramètres du site sauvegardés';
    $messageType = 'success';
}

if (isset($_POST['change_theme'])) {
    $data['settings']['theme'] = $_POST['theme'];
    saveData($data);
    $message = 'Thème changé avec succès';
    $messageType = 'success';
}

if (isset($_POST['save_general_settings'])) {
    $data['settings']['currency'] = $_POST['currency'];
    $data['settings']['whatsapp_message'] = $_POST['whatsapp_message'];
    saveData($data);
    $message = 'Paramètres généraux sauvegardés';
    $messageType = 'success';
}

// ============================================================================
// STATISTIQUES
// ============================================================================

$totalProducts = count($data['products']);
$totalOrders = count($data['orders']);
$totalCustomers = count($data['customers']);
$totalRevenue = array_sum(array_map(fn($o) => ($o['status'] === 'terminee') ? ($o['total'] ?? 0) : 0, $data['orders']));

$pendingOrders = array_filter($data['orders'], fn($o) => ($o['status'] ?? '') === 'en_attente');
$processingOrders = array_filter($data['orders'], fn($o) => ($o['status'] ?? '') === 'en_cours');
$completedOrders = array_filter($data['orders'], fn($o) => ($o['status'] ?? '') === 'terminee');
$cancelledOrders = array_filter($data['orders'], fn($o) => ($o['status'] ?? '') === 'annulee');

$lowStockProducts = array_filter($data['products'], fn($p) => ($p['stock'] ?? 10) <= 5);
$recentOrders = array_slice(array_reverse($data['orders']), 0, 10);

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

function formatPrice($price) {
    global $data;
    return number_format($price, 0, ',', ' ') . ' ' . ($data['settings']['currency'] ?? 'FCFA');
}

function getStatusBadge($status) {
    $badges = [
        'en_attente' => '<span class="badge badge-warning">En attente</span>',
        'en_cours' => '<span class="badge badge-info">En cours</span>',
        'terminee' => '<span class="badge badge-success">Terminée</span>',
        'annulee' => '<span class="badge badge-danger">Annulée</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-default">' . htmlspecialchars($status) . '</span>';
}

function getDeliveryBadge($status) {
    $badges = [
        'livre' => '<span class="badge badge-success">Livré</span>',
        'non_livre' => '<span class="badge badge-warning">Non livré</span>',
        'en_cours' => '<span class="badge badge-info">En cours</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-default">' . htmlspecialchars($status) . '</span>';
}

function getGalleryImages() {
    $images = [];
    if (is_dir(GALLERY_DIR)) {
        $files = glob(GALLERY_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            $images[] = [
                'name' => basename($file),
                'url' => GALLERY_DIR . basename($file)
            ];
        }
    }
    return $images;
}

function getProductImages() {
    $images = [];
    if (is_dir(PRODUCTS_DIR)) {
        $files = glob(PRODUCTS_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            $images[] = [
                'name' => basename($file),
                'url' => PRODUCTS_DIR . basename($file)
            ];
        }
    }
    return $images;
}

$galleryImages = getGalleryImages();
$productImages = getProductImages();
$allImages = array_merge($galleryImages, $productImages);
$currentTheme = $data['settings']['theme'] ?? 'theme-1-rose';

// Récupérer le produit à éditer
$editingProduct = null;
if (isset($_GET['edit']) && $page === 'products') {
    $editId = intval($_GET['edit']);
    foreach ($data['products'] as $product) {
        if ($product['id'] === $editId) {
            $editingProduct = $product;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($data['site']['name'] ?? 'Ma Boutique'); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($currentTheme); ?>.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            color: var(--dark);
        }
        
        /* Login Page */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-mid) 50%, var(--bg-gradient-end) 100%);
            padding: 2rem;
        }
        
        .login-box {
            background: white;
            padding: 2.5rem;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo img {
            height: 80px;
            margin-bottom: 1rem;
        }
        
        .login-logo h1 {
            font-size: 1.5rem;
            color: var(--dark);
        }
        
        .login-logo p {
            color: var(--gray);
            font-size: 0.875rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        }
        
        .btn {
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--border);
        }
        
        .btn-danger {
            background: var(--error);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .error-message {
            background: #fee2e2;
            color: var(--error);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Admin Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-logo img {
            height: 50px;
            width: auto;
        }
        
        .sidebar-logo-text h3 {
            font-size: 0.95rem;
            color: var(--dark);
        }
        
        .sidebar-logo-text p {
            font-size: 0.7rem;
            color: var(--gray);
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-title {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--gray);
            margin-bottom: 0.5rem;
            padding-left: 0.75rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 10px;
            text-decoration: none;
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 0.25rem;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(236, 72, 153, 0.1);
            color: var(--primary);
        }
        
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .btn-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--light);
            border-radius: 10px;
            text-decoration: none;
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-back:hover {
            background: var(--border);
            color: var(--dark);
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            min-height: 100vh;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .btn-logout {
            padding: 0.5rem 1rem;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--gray);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        
        .btn-logout:hover {
            background: #fee2e2;
            color: var(--error);
            border-color: var(--error);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .stat-card-icon.pink { background: rgba(236, 72, 153, 0.1); color: var(--primary); }
        .stat-card-icon.purple { background: rgba(139, 92, 246, 0.1); color: var(--secondary); }
        .stat-card-icon.green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-card-icon.orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-card-icon.blue { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .stat-card-icon.red { background: rgba(239, 68, 68, 0.1); color: var(--error); }
        
        .stat-card-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .stat-card-label {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            margin-bottom: 1.25rem;
        }
        
        .card-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .card-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            font-size: 0.85rem;
        }
        
        th {
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }
        
        td {
            border-bottom: 1px solid var(--border);
        }
        
        tr:hover td {
            background: var(--light);
        }
        
        .product-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .product-thumb {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-default { background: var(--light); color: var(--gray); }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        /* Image Upload */
        .image-upload-area {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: var(--light);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .image-upload-area:hover {
            border-color: var(--primary);
            background: rgba(236, 72, 153, 0.05);
        }
        
        .image-upload-area i {
            font-size: 2rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .image-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 0.75rem;
        }
        
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .gallery-item:hover {
            border-color: var(--primary);
        }
        
        .gallery-item.selected {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.2);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Theme Grid */
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }
        
        .theme-card {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .theme-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .theme-card.active {
            border-color: var(--primary);
            background: rgba(236, 72, 153, 0.05);
        }
        
        .theme-preview {
            height: 60px;
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }
        
        .theme-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .theme-desc {
            font-size: 0.7rem;
            color: var(--gray);
        }
        
        /* Reset Stats Section */
        .reset-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .reset-option {
            background: var(--light);
            padding: 1.25rem;
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .reset-option:hover {
            border-color: var(--primary);
        }
        
        .reset-option.selected {
            border-color: var(--primary);
            background: rgba(236, 72, 153, 0.05);
        }
        
        .reset-option h4 {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .reset-option p {
            font-size: 0.8rem;
            color: var(--gray);
        }
        
        .danger-zone {
            border: 2px solid var(--error);
            background: #fef2f2;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .danger-zone h3 {
            color: var(--error);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .modal-close {
            width: 36px;
            height: 36px;
            background: var(--light);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: var(--border);
        }
        
        .modal-body {
            padding: 1.25rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
        
        /* Action buttons */
        .btn-action {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--light);
            color: var(--gray);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-action:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-action.delete:hover {
            background: var(--error);
        }
    </style>
</head>
<body>
    <?php if ($showLogin): ?>
    <!-- Page de connexion -->
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <?php if (file_exists($data['site']['logo'] ?? 'logo.png')): ?>
                    <img src="<?php echo htmlspecialchars($data['site']['logo'] ?? 'logo.png'); ?>" alt="Logo">
                <?php else: ?>
                    <i class="fas fa-store" style="font-size: 3rem; color: var(--primary);"></i>
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($data['site']['name'] ?? 'Ma Boutique'); ?></h1>
                <p>Administration</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" name="login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            
            <p style="margin-top: 1.5rem; text-align: center; font-size: 0.8rem; color: var(--gray);">
                Identifiants par défaut : admin / admin123
            </p>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Interface d'administration -->
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <?php if (file_exists($data['site']['logo'] ?? 'logo.png')): ?>
                        <img src="<?php echo htmlspecialchars($data['site']['logo'] ?? 'logo.png'); ?>" alt="Logo">
                    <?php endif; ?>
                    <div class="sidebar-logo-text">
                        <h3><?php echo htmlspecialchars($data['site']['name'] ?? 'Ma Boutique'); ?></h3>
                        <p>Administration</p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Général</div>
                    <a href="?page=dashboard" class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        Tableau de bord
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Gestion</div>
                    <a href="?page=products" class="nav-item <?php echo $page === 'products' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        Produits
                    </a>
                    <a href="?page=categories" class="nav-item <?php echo $page === 'categories' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        Catégories
                    </a>
                    <a href="?page=orders" class="nav-item <?php echo $page === 'orders' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i>
                        Commandes
                    </a>
                    <a href="?page=gallery" class="nav-item <?php echo $page === 'gallery' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i>
                        Galerie
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Configuration</div>
                    <a href="?page=settings" class="nav-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        Paramètres
                    </a>
                    <a href="?page=themes" class="nav-item <?php echo $page === 'themes' ? 'active' : ''; ?>">
                        <i class="fas fa-palette"></i>
                        Thèmes
                    </a>
                    <a href="?page=security" class="nav-item <?php echo $page === 'security' ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt"></i>
                        Sécurité
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="index.php" class="btn-back" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    Voir le site
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>
                    <?php
                    $pageTitles = [
                        'dashboard' => 'Tableau de bord',
                        'products' => 'Gestion des produits',
                        'categories' => 'Gestion des catégories',
                        'orders' => 'Gestion des commandes',
                        'gallery' => 'Galerie d\'images',
                        'settings' => 'Paramètres du site',
                        'themes' => 'Choix du thème',
                        'security' => 'Sécurité & Mot de passe'
                    ];
                    echo $pageTitles[$page] ?? 'Administration';
                    ?>
                </h1>
                <div class="user-menu">
                    <div class="user-avatar">A</div>
                    <a href="?logout" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Déconnexion
                    </a>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- DASHBOARD -->
            <?php if ($page === 'dashboard'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-icon pink">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-card-value"><?php echo $totalProducts; ?></div>
                        <div class="stat-card-label">Produits</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon purple">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-card-value"><?php echo $totalOrders; ?></div>
                        <div class="stat-card-label">Commandes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon green">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-card-value"><?php echo $totalCustomers; ?></div>
                        <div class="stat-card-label">Clients</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon orange">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-card-value"><?php echo formatPrice($totalRevenue); ?></div>
                        <div class="stat-card-label">Revenus</div>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-icon blue">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-card-value"><?php echo count($pendingOrders); ?></div>
                        <div class="stat-card-label">En attente</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-icon red">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-card-value"><?php echo count($lowStockProducts); ?></div>
                        <div class="stat-card-label">Stock faible</div>
                    </div>
                </div>
                
                <?php if (count($recentOrders) > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h2>Commandes récentes</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['id'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></td>
                                    <td><?php echo formatPrice($order['total'] ?? 0); ?></td>
                                    <td><?php echo getStatusBadge($order['status'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($order['created_at'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php elseif ($page === 'products'): ?>
                <!-- GESTION DES PRODUITS -->
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo $editingProduct ? 'Modifier le produit' : 'Ajouter un produit'; ?></h2>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <?php if ($editingProduct): ?>
                                <input type="hidden" name="product_id" value="<?php echo $editingProduct['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nom du produit *</label>
                                    <input type="text" name="product_name" required value="<?php echo htmlspecialchars($editingProduct['name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Marque</label>
                                    <input type="text" name="product_brand" value="<?php echo htmlspecialchars($editingProduct['brand'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Catégorie *</label>
                                    <select name="product_category" required>
                                        <?php foreach ($data['categories'] as $cat): ?>
                                            <?php if ($cat['id'] !== 'tous'): ?>
                                            <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($editingProduct['category'] ?? '') === $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Genre</label>
                                    <select name="product_gender">
                                        <?php foreach ($data['genders'] ?? ['Tous', 'Femme', 'Homme', 'Mixte'] as $gender): ?>
                                            <option value="<?php echo htmlspecialchars($gender); ?>" <?php echo ($editingProduct['gender'] ?? '') === $gender ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($gender); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Prix (FCFA) *</label>
                                    <input type="number" name="product_price" required value="<?php echo $editingProduct['price'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Ancien prix (FCFA)</label>
                                    <input type="number" name="product_old_price" value="<?php echo $editingProduct['old_price'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Stock</label>
                                    <input type="number" name="product_stock" value="<?php echo $editingProduct['stock'] ?? 0; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Badge</label>
                                    <select name="product_badge">
                                        <option value="">Aucun</option>
                                        <option value="nouveau" <?php echo ($editingProduct['badge'] ?? '') === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                                        <option value="promo" <?php echo ($editingProduct['badge'] ?? '') === 'promo' ? 'selected' : ''; ?>>Promo</option>
                                        <option value="populaire" <?php echo ($editingProduct['badge'] ?? '') === 'populaire' ? 'selected' : ''; ?>>Populaire</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Image principale</label>
                                <div style="display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 250px;">
                                        <input type="text" name="product_image" id="product_image" placeholder="URL de l'image" value="<?php echo htmlspecialchars($editingProduct['image'] ?? ''); ?>" style="margin-bottom: 0.5rem;">
                                        <p style="font-size: 0.8rem; color: var(--gray);">Ou uploadez une image :</p>
                                        <input type="file" name="product_main_image" accept="image/*" id="main_image_input">
                                    </div>
                                    <div id="main_image_preview">
                                        <?php if ($editingProduct && $editingProduct['image']): ?>
                                            <img src="<?php echo htmlspecialchars($editingProduct['image']); ?>" class="image-preview">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (count($allImages) > 0): ?>
                            <div class="form-group full-width">
                                <label>Ou sélectionnez dans la galerie</label>
                                <div class="gallery-grid" style="max-height: 150px; overflow-y: auto;">
                                    <?php foreach ($allImages as $img): ?>
                                        <div class="gallery-item" onclick="selectImage('<?php echo htmlspecialchars($img['url']); ?>', 'product_image', this)">
                                            <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="product_description" rows="3"><?php echo htmlspecialchars($editingProduct['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <?php if ($editingProduct): ?>
                                    <a href="?page=products" class="btn btn-secondary">Annuler</a>
                                    <button type="submit" name="edit_product" class="btn btn-primary">
                                        <i class="fas fa-save"></i>
                                        Modifier
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="add_product" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        Ajouter
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Liste des produits -->
                <div class="card">
                    <div class="card-header">
                        <h2>Liste des produits (<?php echo $totalProducts; ?>)</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['products'] as $product): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?php echo htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/50'); ?>" class="product-thumb" alt="">
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <?php if ($product['brand'] ?? ''): ?>
                                                    <br><small><?php echo htmlspecialchars($product['brand']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category'] ?? ''); ?></td>
                                    <td><?php echo formatPrice($product['price']); ?></td>
                                    <td>
                                        <?php if (($product['stock'] ?? 10) <= 5): ?>
                                            <span class="badge badge-danger"><?php echo $product['stock'] ?? 0; ?></span>
                                        <?php else: ?>
                                            <?php echo $product['stock'] ?? 0; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?page=products&edit=<?php echo $product['id']; ?>" class="btn-action" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?page=products&delete_product=<?php echo $product['id']; ?>" class="btn-action delete" title="Supprimer" onclick="return confirm('Supprimer ce produit ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($page === 'categories'): ?>
                <!-- GESTION DES CATÉGORIES -->
                <div class="card">
                    <div class="card-header">
                        <h2>Ajouter une catégorie</h2>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nom de la catégorie *</label>
                                    <input type="text" name="category_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Icône (emoji)</label>
                                    <input type="text" name="category_icon" placeholder="📦" maxlength="10">
                                </div>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                Ajouter
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Liste des catégories</h2>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Icône</th>
                                    <th>Nom</th>
                                    <th>Produits</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['categories'] as $category): ?>
                                <tr>
                                    <td style="font-size: 1.5rem;"><?php echo htmlspecialchars($category['icon'] ?? '📦'); ?></td>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo $category['count'] ?? 0; ?></td>
                                    <td>
                                        <?php if ($category['id'] !== 'tous'): ?>
                                        <a href="?page=categories&delete_category=<?php echo htmlspecialchars($category['id']); ?>" class="btn-action delete" onclick="return confirm('Supprimer cette catégorie ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($page === 'orders'): ?>
                <!-- GESTION DES COMMANDES -->
                <div class="card">
                    <div class="card-header">
                        <h2>Liste des commandes</h2>
                    </div>
                    <?php if (count($data['orders']) === 0): ?>
                        <div class="card-body">
                            <p style="color: var(--gray); text-align: center; padding: 2rem;">
                                Aucune commande pour le moment
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Téléphone</th>
                                        <th>Articles</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                        <th>Livraison</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['orders'] as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></td>
                                        <td><?php echo count($order['items'] ?? []); ?></td>
                                        <td><?php echo formatPrice($order['total'] ?? 0); ?></td>
                                        <td><?php echo getStatusBadge($order['status'] ?? ''); ?></td>
                                        <td><?php echo getDeliveryBadge($order['delivery_status'] ?? ''); ?></td>
                                        <td>
                                            <button class="btn-action" onclick="editOrder('<?php echo htmlspecialchars(json_encode($order)); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($page === 'gallery'): ?>
                <!-- GALERIE D'IMAGES -->
                <div class="card">
                    <div class="card-header">
                        <h2>Uploader une image</h2>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="image-upload-area" onclick="document.getElementById('image_file').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Cliquez ou glissez une image ici</p>
                                <p style="font-size: 0.8rem; color: var(--gray);">JPG, PNG, GIF, WebP - Max 5MB</p>
                                <input type="file" name="image_file" id="image_file" accept="image/*" style="display: none;" required>
                            </div>
                            <button type="submit" name="upload_image" class="btn btn-primary" style="margin-top: 1rem;">
                                <i class="fas fa-upload"></i>
                                Uploader
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Images disponibles (<?php echo count($allImages); ?>)</h2>
                    </div>
                    <div class="card-body">
                        <div class="gallery-grid">
                            <?php foreach ($allImages as $img): ?>
                                <div class="gallery-item">
                                    <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="">
                                    <a href="?page=gallery&delete_gallery_image=<?php echo htmlspecialchars($img['name']); ?>" 
                                       class="btn-action delete" 
                                       style="position: absolute; top: 4px; right: 4px;"
                                       onclick="return confirm('Supprimer cette image ?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($page === 'settings'): ?>
                <!-- PARAMÈTRES DU SITE -->
                <div class="card">
                    <div class="card-header">
                        <h2>Informations du site</h2>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nom du site</label>
                                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($data['site']['name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Slogan</label>
                                    <input type="text" name="site_tagline" value="<?php echo htmlspecialchars($data['site']['tagline'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Téléphone WhatsApp</label>
                                    <input type="text" name="site_phone" value="<?php echo htmlspecialchars($data['site']['phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="site_email" value="<?php echo htmlspecialchars($data['site']['email'] ?? ''); ?>">
                                </div>
                                <div class="form-group full-width">
                                    <label>Adresse</label>
                                    <input type="text" name="site_address" value="<?php echo htmlspecialchars($data['site']['address'] ?? ''); ?>">
                                </div>
                                <div class="form-group full-width">
                                    <label>Description</label>
                                    <textarea name="site_description" rows="3"><?php echo htmlspecialchars($data['site']['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <h3 style="margin: 1.5rem 0 1rem;">Statistiques affichées</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nombre de produits</label>
                                    <input type="text" name="stat_products" value="<?php echo htmlspecialchars($data['site']['stats']['products'] ?? '100+'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Note</label>
                                    <input type="text" name="stat_rating" value="<?php echo htmlspecialchars($data['site']['stats']['rating'] ?? '4.8'); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Délai de livraison</label>
                                    <input type="text" name="stat_delivery" value="<?php echo htmlspecialchars($data['site']['stats']['delivery'] ?? '24h'); ?>">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="save_site_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Sauvegarder
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Paramètres généraux</h2>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Devise</label>
                                    <select name="currency">
                                        <option value="FCFA" <?php echo ($data['settings']['currency'] ?? '') === 'FCFA' ? 'selected' : ''; ?>>FCFA</option>
                                        <option value="EUR" <?php echo ($data['settings']['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                        <option value="USD" <?php echo ($data['settings']['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                                    </select>
                                </div>
                                <div class="form-group full-width">
                                    <label>Message WhatsApp par défaut</label>
                                    <textarea name="whatsapp_message" rows="2"><?php echo htmlspecialchars($data['settings']['whatsapp_message'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="save_general_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Sauvegarder
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($page === 'themes'): ?>
                <!-- CHOIX DU THÈME -->
                <div class="card">
                    <div class="card-header">
                        <h2>Choisir un thème</h2>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="theme-grid">
                                <?php foreach ($availableThemes as $themeId => $theme): ?>
                                <label class="theme-card <?php echo $currentTheme === $themeId ? 'active' : ''; ?>">
                                    <div class="theme-preview" style="background: linear-gradient(135deg, <?php echo $theme['color']; ?>, <?php echo $theme['color']; ?>88);"></div>
                                    <div class="theme-name"><?php echo $theme['icon']; ?> <?php echo htmlspecialchars($theme['name']); ?></div>
                                    <div class="theme-desc"><?php echo htmlspecialchars($theme['desc']); ?></div>
                                    <input type="radio" name="theme" value="<?php echo $themeId; ?>" <?php echo $currentTheme === $themeId ? 'checked' : ''; ?> style="display: none;">
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_theme" class="btn btn-primary">
                                    <i class="fas fa-palette"></i>
                                    Appliquer le thème
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php elseif ($page === 'security'): ?>
                <!-- SÉCURITÉ -->
                <div class="card">
                    <div class="card-header">
                        <h2>Changer le mot de passe</h2>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label>Mot de passe actuel *</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label>Nouveau mot de passe *</label>
                                    <input type="password" name="new_password" required minlength="6">
                                    <small style="color: var(--gray);">Minimum 6 caractères</small>
                                </div>
                                <div class="form-group">
                                    <label>Confirmer le mot de passe *</label>
                                    <input type="password" name="confirm_password" required minlength="6">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i>
                                    Changer le mot de passe
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- RESET DES STATISTIQUES -->
                <div class="danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Zone de danger - Réinitialisation</h3>
                    <p style="margin-bottom: 1rem; color: var(--gray);">
                        Ces actions sont irréversibles. Vos données seront définitivement supprimées.
                    </p>
                    
                    <form method="post" onsubmit="return confirmReset()">
                        <div class="reset-options">
                            <label class="reset-option">
                                <input type="radio" name="reset_type" value="orders" style="display: none;">
                                <h4><i class="fas fa-shopping-bag"></i> Commandes</h4>
                                <p>Supprimer toutes les commandes</p>
                            </label>
                            <label class="reset-option">
                                <input type="radio" name="reset_type" value="customers" style="display: none;">
                                <h4><i class="fas fa-users"></i> Clients</h4>
                                <p>Supprimer tous les clients et leurs commandes</p>
                            </label>
                            <label class="reset-option">
                                <input type="radio" name="reset_type" value="products" style="display: none;">
                                <h4><i class="fas fa-box"></i> Produits</h4>
                                <p>Supprimer tous les produits et commandes</p>
                            </label>
                            <label class="reset-option">
                                <input type="radio" name="reset_type" value="all" style="display: none;">
                                <h4><i class="fas fa-trash-alt"></i> Tout réinitialiser</h4>
                                <p>Supprimer toutes les données</p>
                            </label>
                        </div>
                        
                        <div class="form-grid" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label>Confirmez votre nom d'utilisateur</label>
                                <input type="text" name="confirm_username" placeholder="Votre nom d'utilisateur" required>
                            </div>
                            <div class="form-group">
                                <label>Confirmez votre mot de passe</label>
                                <input type="password" name="confirm_password" placeholder="Votre mot de passe" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="reset_stats" class="btn btn-danger" style="margin-top: 1rem;">
                            <i class="fas fa-redo"></i>
                            Confirmer la réinitialisation
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Modal pour éditer une commande -->
    <div class="modal-overlay" id="orderModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Modifier la commande</h2>
                <button class="modal-close" onclick="closeOrderModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="order_id" id="modal_order_id">
                    
                    <div class="form-group">
                        <label>Statut de la commande</label>
                        <select name="status" id="modal_status">
                            <option value="en_attente">En attente</option>
                            <option value="en_cours">En cours</option>
                            <option value="terminee">Terminée</option>
                            <option value="annulee">Annulée</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Statut de livraison</label>
                        <select name="delivery_status" id="modal_delivery_status">
                            <option value="non_livre">Non livré</option>
                            <option value="en_cours">En cours</option>
                            <option value="livre">Livré</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nom du livreur (optionnel)</label>
                        <input type="text" name="delivery_name" id="modal_delivery_name">
                    </div>
                    
                    <div class="form-group">
                        <label>Téléphone livreur (optionnel)</label>
                        <input type="text" name="delivery_phone" id="modal_delivery_phone">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeOrderModal()">Annuler</button>
                        <button type="submit" name="update_order_status" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Sélection d'image dans la galerie
        function selectImage(url, inputId, element) {
            document.getElementById(inputId).value = url;
            
            // Désélectionner les autres
            document.querySelectorAll('.gallery-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Sélectionner celui-ci
            element.classList.add('selected');
            
            // Afficher la prévisualisation
            const preview = document.getElementById('main_image_preview');
            if (preview) {
                preview.innerHTML = '<img src="' + url + '" class="image-preview">';
            }
        }
        
        // Prévisualisation de l'image uploadée
        document.getElementById('main_image_input')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('main_image_preview');
                    if (preview) {
                        preview.innerHTML = '<img src="' + e.target.result + '" class="image-preview">';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Modal de commande
        function editOrder(orderJson) {
            const order = JSON.parse(orderJson);
            document.getElementById('modal_order_id').value = order.id;
            document.getElementById('modal_status').value = order.status || 'en_attente';
            document.getElementById('modal_delivery_status').value = order.delivery_status || 'non_livre';
            document.getElementById('modal_delivery_name').value = order.delivery_name || '';
            document.getElementById('modal_delivery_phone').value = order.delivery_phone || '';
            document.getElementById('orderModal').classList.add('active');
        }
        
        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('active');
        }
        
        // Sélection des options de reset
        document.querySelectorAll('.reset-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.reset-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
        
        // Confirmation du reset
        function confirmReset() {
            const selectedReset = document.querySelector('input[name="reset_type"]:checked');
            if (!selectedReset) {
                alert('Veuillez sélectionner ce que vous voulez réinitialiser.');
                return false;
            }
            
            const resetTypes = {
                'orders': 'toutes les commandes',
                'customers': 'tous les clients et leurs commandes',
                'products': 'tous les produits et commandes',
                'all': 'TOUTES les données'
            };
            
            return confirm('Êtes-vous sûr de vouloir supprimer ' + resetTypes[selectedReset.value] + ' ?\n\nCette action est IRRÉVERSIBLE !');
        }
        
        // Thème selection
        document.querySelectorAll('.theme-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
<?php endif; ?>
</body>
</html>
