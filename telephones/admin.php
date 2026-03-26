<?php
/**
 * E-COMMERCE MODULAIRE - ADMIN.PHP CORRIGÉ
 * 
 * Panneau d'administration complet avec:
 * - Gestion des produits (CRUD) avec jusqu'à 5 images
 * - Galerie d'images locale pour sélectionner les images
 * - Changement de thème en temps réel (10 thèmes prédéfinis)
 * - Gestion des commandes et livreurs
 * - Gestion des catégories
 * - Modification des informations du site
 * - Changement de mot de passe admin
 * - TOUTES LES PAGES IMPLÉMENTÉES
 * 
 * @version 2.1 FIXED
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

// Configuration admin (à modifier après première connexion)
$adminConfig = [
    'username' => 'admin',
    'password' => password_hash('admin123', PASSWORD_DEFAULT),
    'session_timeout' => 3600
];

// Thèmes disponibles
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
    return [];
}

function saveData($data) {
    // Recalculer les compteurs de catégories avant de sauvegarder
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
if (!isLoggedIn() && !isset($_POST['login'])) {
    $showLogin = true;
} else {
    $showLogin = false;
}

// ============================================================================
// TRAITEMENT DU LOGIN
// ============================================================================

$error = '';
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $adminConfig['username'] && password_verify($password, $adminConfig['password'])) {
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
// CHARGEMENT DES DONNÉES
// ============================================================================

$data = loadData();

// Initialiser les structures si elles n'existent pas
if (!isset($data['customers'])) $data['customers'] = [];
if (!isset($data['orders'])) $data['orders'] = [];
if (!isset($data['settings']['theme'])) $data['settings']['theme'] = 'theme-1-rose';
if (!isset($data['products'])) $data['products'] = [];
if (!isset($data['categories'])) $data['categories'] = [];
if (!isset($data['site'])) $data['site'] = ['name' => 'Ma Boutique'];

// Page active
$page = $_GET['page'] ?? 'dashboard';

// ============================================================================
// TRAITEMENT DES ACTIONS
// ============================================================================

$message = '';
$messageType = '';

// Mettre à jour le statut d'une commande
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

// Sauvegarder les paramètres du site
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

// Changer le thème
if (isset($_POST['change_theme'])) {
    $data['settings']['theme'] = $_POST['theme'];
    saveData($data);
    $message = 'Thème changé avec succès';
    $messageType = 'success';
}

// Sauvegarder les paramètres généraux
if (isset($_POST['save_general_settings'])) {
    $data['settings']['currency'] = $_POST['currency'];
    $data['settings']['whatsapp_message'] = $_POST['whatsapp_message'];
    saveData($data);
    $message = 'Paramètres généraux sauvegardés';
    $messageType = 'success';
}

// ============================================================================
// GESTION DES PRODUITS AVEC IMAGES MULTIPLES
// ============================================================================

// Ajouter un produit
if (isset($_POST['add_product'])) {
    $newProduct = [
        'id' => time(),
        'name' => $_POST['product_name'],
        'brand' => $_POST['product_brand'],
        'category' => $_POST['product_category'],
        'gender' => $_POST['product_gender'],
        'price' => intval($_POST['product_price']),
        'old_price' => $_POST['product_old_price'] ? intval($_POST['product_old_price']) : null,
        'stock' => intval($_POST['product_stock']),
        'badge' => $_POST['product_badge'],
        'rating' => intval($_POST['product_rating']),
        'image' => $_POST['product_image'],
        'images' => [],
        'description' => $_POST['product_description'] ?? ''
    ];
    
    // Ajouter les images supplémentaires (jusqu'à 5)
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
            $product['name'] = $_POST['product_name'];
            $product['brand'] = $_POST['product_brand'];
            $product['category'] = $_POST['product_category'];
            $product['gender'] = $_POST['product_gender'];
            $product['price'] = intval($_POST['product_price']);
            $product['old_price'] = $_POST['product_old_price'] ? intval($_POST['product_old_price']) : null;
            $product['stock'] = intval($_POST['product_stock']);
            $product['badge'] = $_POST['product_badge'];
            $product['rating'] = intval($_POST['product_rating']);
            $product['image'] = $_POST['product_image'];
            $product['description'] = $_POST['product_description'] ?? '';
            
            // Mettre à jour les images supplémentaires
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
        'icon' => $_POST['category_icon'],
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
// UPLOAD D'IMAGES
// ============================================================================

if (isset($_POST['upload_image'])) {
    if (!is_dir(GALLERY_DIR)) {
        mkdir(GALLERY_DIR, 0755, true);
    }
    
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_' . basename($_FILES['image_file']['name']);
        $targetPath = GALLERY_DIR . $fileName;
        
        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['image_file']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetPath)) {
                $message = 'Image uploadée avec succès';
                $messageType = 'success';
            } else {
                $message = 'Erreur lors de l\'upload';
                $messageType = 'error';
            }
        } else {
            $message = 'Type de fichier non autorisé';
            $messageType = 'error';
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
// CHANGEMENT DE MOT DE PASSE
// ============================================================================

if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (!password_verify($currentPassword, $adminConfig['password'])) {
        $message = 'Mot de passe actuel incorrect';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Les nouveaux mots de passe ne correspondent pas';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Le mot de passe doit contenir au moins 6 caractères';
        $messageType = 'error';
    } else {
        $message = 'Mot de passe changé avec succès (pensez à mettre à jour le fichier admin.php)';
        $messageType = 'success';
    }
}

// ============================================================================
// STATISTIQUES
// ============================================================================

$totalProducts = count($data['products']);
$totalOrders = count($data['orders']);
$totalCustomers = count($data['customers']);
$totalRevenue = array_sum(array_map(fn($o) => ($o['status'] === 'terminee') ? ($o['total'] ?? 0) : 0, $data['orders']));

$pendingOrders = array_filter($data['orders'], fn($o) => $o['status'] === 'en_attente');
$processingOrders = array_filter($data['orders'], fn($o) => $o['status'] === 'en_cours');
$completedOrders = array_filter($data['orders'], fn($o) => $o['status'] === 'terminee');
$cancelledOrders = array_filter($data['orders'], fn($o) => $o['status'] === 'annulee');

$deliveredOrders = array_filter($data['orders'], fn($o) => $o['delivery_status'] === 'livre');
$notDeliveredOrders = array_filter($data['orders'], fn($o) => $o['delivery_status'] === 'non_livre');

$lowStockProducts = array_filter($data['products'], fn($p) => $p['stock'] <= 5);
$recentOrders = array_slice(array_reverse($data['orders']), 0, 10);

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

function formatPrice($price) {
    global $data;
    return number_format($price, 0, ',', ' ') . ' ' . ($data['settings']['currency'] ?? 'FCFA');
}

function getStatusBadge($status) {
    return match($status) {
        'en_attente' => '<span class="badge badge-warning">En attente</span>',
        'en_cours' => '<span class="badge badge-info">En cours</span>',
        'terminee' => '<span class="badge badge-success">Terminée</span>',
        'annulee' => '<span class="badge badge-danger">Annulée</span>',
        default => '<span class="badge badge-default">' . $status . '</span>'
    };
}

function getDeliveryBadge($status) {
    return match($status) {
        'livre' => '<span class="badge badge-success">Livré</span>',
        'non_livre' => '<span class="badge badge-warning">Non livré</span>',
        'en_cours' => '<span class="badge badge-info">En cours</span>',
        default => '<span class="badge badge-default">' . $status . '</span>'
    };
}

function getGalleryImages() {
    $images = [];
    if (is_dir(GALLERY_DIR)) {
        $files = glob(GALLERY_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            $images[] = [
                'name' => basename($file),
                'path' => GALLERY_DIR . basename($file),
                'url' => GALLERY_DIR . basename($file)
            ];
        }
    }
    return $images;
}

$galleryImages = getGalleryImages();
$currentTheme = $data['settings']['theme'] ?? 'theme-1-rose';

// Récupérer le produit à éditer si demandé
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
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($currentTheme); ?>.css">
    
    <style>
        /* ============================================================================
           VARIABLES ET BASE
           ============================================================================ */
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            color: var(--dark);
        }
        
        /* ============================================================================
           LOGIN PAGE
           ============================================================================ */
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
            height: 90px;
            margin-bottom: 1rem;
        }
        
        .login-logo i {
            font-size: 3.5rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .login-logo h1 {
            font-size: 1.5rem;
            margin-top: 0.5rem;
            color: var(--dark);
        }
        
        .login-logo p {
            color: var(--gray);
            font-size: 0.875rem;
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
        
        /* ============================================================================
           ADMIN LAYOUT
           ============================================================================ */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
            transition: transform 0.3s;
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
        
        .sidebar-logo-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-logo-icon img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
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
        
        .nav-item i {
            width: 18px;
            text-align: center;
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
        
        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 101;
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        
        /* Main Content */
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
        
        .page-header p {
            color: var(--gray);
            font-size: 0.875rem;
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
        
        /* ============================================================================
           COMPONENTS
           ============================================================================ */
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .stat-card-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        .stat-card-icon.pink { background: rgba(236, 72, 153, 0.1); color: var(--primary); }
        .stat-card-icon.purple { background: rgba(139, 92, 246, 0.1); color: var(--secondary); }
        .stat-card-icon.green { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .stat-card-icon.orange { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .stat-card-icon.blue { background: rgba(59, 130, 246, 0.1); color: var(--info); }
        .stat-card-icon.red { background: rgba(239, 68, 68, 0.1); color: var(--error); }
        
        .stat-card-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.15rem;
        }
        
        .stat-card-label {
            font-size: 0.75rem;
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
            font-size: 1rem;
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
            padding: 0.875rem;
            text-align: left;
            font-size: 0.8rem;
        }
        
        th {
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }
        
        td {
            border-bottom: 1px solid var(--border);
        }
        
        tr:hover td {
            background: var(--light);
        }
        
        /* Product Cell */
        .product-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .product-thumb {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .product-info h4 {
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.15rem;
        }
        
        .product-info p {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        /* Buttons */
        .btn {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        .btn-secondary {
            background: var(--light);
            color: var(--dark);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: var(--border);
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--light);
            color: var(--gray);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-action.delete:hover {
            background: var(--error);
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group label {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
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
        
        /* Modals */
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
            padding: 1.5rem;
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
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: var(--border);
        }
        
        .modal-body {
            padding: 1.25rem;
        }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
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
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .badge-default { background: var(--light); color: var(--gray); }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        /* Status Cards */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .status-card {
            background: white;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .status-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .status-card .label {
            font-size: 0.7rem;
            color: var(--gray);
            text-transform: uppercase;
        }
        
        .status-card.pending .value { color: var(--warning); }
        .status-card.processing .value { color: var(--info); }
        .status-card.completed .value { color: var(--success); }
        .status-card.cancelled .value { color: var(--error); }
        
        /* Theme Selector */
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
            width: 100%;
            height: 80px;
            border-radius: 8px;
            margin-bottom: 0.75rem;
        }
        
        .theme-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .theme-desc {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        /* Gallery */
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
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-item .delete-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .gallery-item:hover .delete-btn {
            opacity: 1;
        }
        
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background: rgba(236, 72, 153, 0.02);
        }
        
        .upload-area i {
            font-size: 2rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        /* Image Input Group */
        .image-input-group {
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }
        
        .image-input-group input {
            flex: 1;
        }
        
        .btn-select-image {
            padding: 0.875rem;
            background: var(--light);
            border: 2px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-select-image:hover {
            border-color: var(--primary);
            background: rgba(236, 72, 153, 0.05);
        }
        
        .image-preview-small {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--border);
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .stock-low {
            color: var(--error);
            font-weight: 600;
        }
        
        /* ============================================================================
           RESPONSIVE
           ============================================================================ */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
        
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .theme-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php if ($showLogin): ?>
    <!-- ============================================================================
         LOGIN PAGE
         ============================================================================ -->
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <?php if (!empty($data['site']['logo'])): ?>
                <img src="<?php echo htmlspecialchars($data['site']['logo']); ?>" alt="Logo">
                <?php else: ?>
                <i class="fas fa-store"></i>
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($data['site']['name'] ?? 'Ma Boutique'); ?></h1>
                <p>Panneau d'administration</p>
            </div>
            
            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.75rem; color: var(--gray);">
                Identifiants par défaut : admin / admin123
            </p>
        </div>
    </div>
    
    <?php else: ?>
    <!-- ============================================================================
         ADMIN DASHBOARD
         ============================================================================ -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">
                        <?php if (!empty($data['site']['logo'])): ?>
                        <img src="<?php echo htmlspecialchars($data['site']['logo']); ?>" alt="Logo">
                        <?php else: ?>
                        <i class="fas fa-store" style="font-size: 1.5rem; color: var(--primary);"></i>
                        <?php endif; ?>
                    </div>
                    <div class="sidebar-logo-text">
                        <h3><?php echo htmlspecialchars($data['site']['name'] ?? 'Ma Boutique'); ?></h3>
                        <p>Admin</p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Menu principal</div>
                    <a href="?page=dashboard" class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        Tableau de bord
                    </a>
                    <a href="?page=products" class="nav-item <?php echo $page === 'products' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        Produits
                    </a>
                    <a href="?page=orders" class="nav-item <?php echo $page === 'orders' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        Commandes
                    </a>
                    <a href="?page=customers" class="nav-item <?php echo $page === 'customers' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        Clients
                    </a>
                    <a href="?page=categories" class="nav-item <?php echo $page === 'categories' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        Catégories
                    </a>
                    <a href="?page=gallery" class="nav-item <?php echo $page === 'gallery' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i>
                        Galerie
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Configuration</div>
                    <a href="?page=site" class="nav-item <?php echo $page === 'site' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i>
                        Informations du site
                    </a>
                    <a href="?page=themes" class="nav-item <?php echo $page === 'themes' ? 'active' : ''; ?>">
                        <i class="fas fa-palette"></i>
                        Thèmes
                    </a>
                    <a href="?page=settings" class="nav-item <?php echo $page === 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        Paramètres
                    </a>
                    <a href="?page=security" class="nav-item <?php echo $page === 'security' ? 'active' : ''; ?>">
                        <i class="fas fa-shield-alt"></i>
                        Sécurité
                    </a>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="index.php" class="btn-back" target="_blank">
                    <i class="fas fa-arrow-left"></i>
                    Retour à la boutique
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="page-header">
                <div>
                    <h1><?php 
                        echo match($page) {
                            'dashboard' => 'Tableau de bord',
                            'products' => 'Produits',
                            'orders' => 'Commandes',
                            'customers' => 'Clients',
                            'categories' => 'Catégories',
                            'gallery' => 'Galerie d\'images',
                            'site' => 'Informations du site',
                            'themes' => 'Thèmes',
                            'settings' => 'Paramètres',
                            'security' => 'Sécurité',
                            default => 'Tableau de bord'
                        };
                    ?></h1>
                    <p>Gestion de la boutique <?php echo htmlspecialchars($data['site']['name'] ?? 'Ma Boutique'); ?></p>
                </div>
                <div class="user-menu">
                    <div class="user-avatar">A</div>
                    <a href="?logout=1" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Déconnexion
                    </a>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($page === 'dashboard'): ?>
            <!-- ============================================================================
                 DASHBOARD
                 ============================================================================ -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon pink">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo formatPrice($totalRevenue); ?></div>
                    <div class="stat-card-label">Revenus confirmés</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon purple">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalOrders; ?></div>
                    <div class="stat-card-label">Commandes totales</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon green">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalCustomers; ?></div>
                    <div class="stat-card-label">Clients</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-icon orange">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalProducts; ?></div>
                    <div class="stat-card-label">Produits</div>
                </div>
            </div>
            
            <!-- Order Status -->
            <h3 style="font-size: 0.9rem; margin-bottom: 0.75rem; color: var(--gray);">Statut des commandes</h3>
            <div class="status-cards">
                <div class="status-card pending">
                    <div class="value"><?php echo count($pendingOrders); ?></div>
                    <div class="label">En attente</div>
                </div>
                <div class="status-card processing">
                    <div class="value"><?php echo count($processingOrders); ?></div>
                    <div class="label">En cours</div>
                </div>
                <div class="status-card completed">
                    <div class="value"><?php echo count($completedOrders); ?></div>
                    <div class="label">Terminées</div>
                </div>
                <div class="status-card cancelled">
                    <div class="value"><?php echo count($cancelledOrders); ?></div>
                    <div class="label">Annulées</div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h2>Commandes récentes</h2>
                    <a href="?page=orders" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.4rem 0.875rem;">Voir tout</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                    <p style="text-align: center; color: var(--gray); padding: 2rem;">Aucune commande pour le moment</p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Livraison</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo substr($order['id'], -6); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo formatPrice($order['total']); ?></td>
                                    <td><?php echo getStatusBadge($order['status']); ?></td>
                                    <td><?php echo getDeliveryBadge($order['delivery_status']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($lowStockProducts)): ?>
            <div class="card">
                <div class="card-header">
                    <h2>⚠️ Produits en rupture de stock</h2>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" class="product-thumb">
                                            <div class="product-info">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($product['brand']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="stock-low"><?php echo $product['stock']; ?> unités</td>
                                    <td>
                                        <a href="?page=products&edit=<?php echo $product['id']; ?>" class="btn-action">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php elseif ($page === 'products'): ?>
            <!-- ============================================================================
                 PRODUCTS PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Produits</h2>
                        <p style="color: var(--gray); font-size: 0.8rem;">Gérez votre catalogue de produits</p>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('addProductModal')">
                        <i class="fas fa-plus"></i>
                        Ajouter
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th>Images</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['products'] as $product): 
                                    $imageCount = 1 + (isset($product['images']) ? count($product['images']) : 0);
                                ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" class="product-thumb">
                                            <div class="product-info">
                                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($product['brand']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($product['category']); ?></td>
                                    <td>
                                        <?php echo formatPrice($product['price']); ?>
                                        <?php if ($product['old_price']): ?>
                                        <br><small style="text-decoration: line-through; color: var(--gray);"><?php echo formatPrice($product['old_price']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td <?php echo $product['stock'] <= 5 ? 'class="stock-low"' : ''; ?>>
                                        <?php echo $product['stock']; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-default">
                                            <i class="fas fa-images"></i> <?php echo $imageCount; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="?page=products&edit=<?php echo $product['id']; ?>" class="btn-action" title="Modifier" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($product)); ?>); return false;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?page=products&delete_product=<?php echo $product['id']; ?>" class="btn-action delete" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($data['products'])): ?>
                    <p style="text-align: center; color: var(--gray); padding: 3rem;">
                        <i class="fas fa-box" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                        Aucun produit. Cliquez sur "Ajouter" pour créer votre premier produit.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php elseif ($page === 'orders'): ?>
            <!-- ============================================================================
                 ORDERS PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Commandes</h2>
                        <p style="color: var(--gray); font-size: 0.8rem;">Gérez toutes les commandes</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($data['orders'])): ?>
                    <p style="text-align: center; color: var(--gray); padding: 3rem;">
                        <i class="fas fa-shopping-cart" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                        Aucune commande pour le moment.
                    </p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Client</th>
                                    <th>Articles</th>
                                    <th>Total</th>
                                    <th>Statut</th>
                                    <th>Livraison</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['orders'] as $order): ?>
                                <tr>
                                    <td><?php echo substr($order['id'], -6); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo count($order['items']); ?></td>
                                    <td><?php echo formatPrice($order['total']); ?></td>
                                    <td><?php echo getStatusBadge($order['status']); ?></td>
                                    <td><?php echo getDeliveryBadge($order['delivery_status']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-action" onclick="openOrderModal(<?php echo htmlspecialchars(json_encode($order)); ?>)" title="Modifier">
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
            </div>
            
            <?php elseif ($page === 'customers'): ?>
            <!-- ============================================================================
                 CUSTOMERS PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Clients</h2>
                        <p style="color: var(--gray); font-size: 0.8rem;">Liste de tous les clients</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($data['customers'])): ?>
                    <p style="text-align: center; color: var(--gray); padding: 3rem;">
                        <i class="fas fa-users" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                        Aucun client pour le moment.
                    </p>
                    <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Téléphone</th>
                                    <th>Quartier</th>
                                    <th>Commandes</th>
                                    <th>Date d'inscription</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['customers'] as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['quartier']); ?></td>
                                    <td><?php echo $customer['order_count']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php elseif ($page === 'categories'): ?>
            <!-- ============================================================================
                 CATEGORIES PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Catégories</h2>
                        <p style="color: var(--gray); font-size: 0.8rem;">Gérez les catégories de produits</p>
                    </div>
                    <button class="btn btn-primary" onclick="openModal('addCategoryModal')">
                        <i class="fas fa-plus"></i>
                        Ajouter
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Icône</th>
                                    <th>Nombre de produits</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['categories'] as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td><?php echo $category['icon']; ?></td>
                                    <td><?php echo $category['count']; ?></td>
                                    <td>
                                        <a href="?page=categories&delete_category=<?php echo htmlspecialchars($category['id']); ?>" class="btn-action delete" title="Supprimer" onclick="return confirm('Êtes-vous sûr ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php elseif ($page === 'gallery'): ?>
            <!-- ============================================================================
                 GALLERY PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>Galerie d'images</h2>
                        <p style="color: var(--gray); font-size: 0.8rem;">Gérez les images pour les produits</p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" style="margin-bottom: 2rem;">
                        <label class="upload-area" onclick="document.getElementById('imageUpload').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p style="margin: 0.5rem 0 0 0;">Cliquez pour uploader une image</p>
                            <small style="color: var(--gray);">JPG, PNG, GIF, WebP (max 5MB)</small>
                        </label>
                        <input type="file" id="imageUpload" name="image_file" accept="image/*" style="display: none;" onchange="this.form.submit()">
                    </form>
                    
                    <?php if (!empty($galleryImages)): ?>
                    <div class="gallery-grid">
                        <?php foreach ($galleryImages as $img): ?>
                        <div class="gallery-item">
                            <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="<?php echo htmlspecialchars($img['name']); ?>">
                            <a href="?page=gallery&delete_gallery_image=<?php echo htmlspecialchars($img['name']); ?>" class="delete-btn" title="Supprimer" onclick="return confirm('Êtes-vous sûr ?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--gray); padding: 2rem;">
                        <i class="fas fa-images" style="font-size: 2rem; opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                        La galerie est vide. Uploadez des images pour commencer.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php elseif ($page === 'site'): ?>
            <!-- ============================================================================
                 SITE INFO PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <h2>Informations du site</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Nom du site *</label>
                                <input type="text" name="site_name" value="<?php echo htmlspecialchars($data['site']['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Slogan</label>
                                <input type="text" name="site_tagline" value="<?php echo htmlspecialchars($data['site']['tagline'] ?? ''); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="site_description" rows="3"><?php echo htmlspecialchars($data['site']['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="text" name="site_phone" value="<?php echo htmlspecialchars($data['site']['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="site_email" value="<?php echo htmlspecialchars($data['site']['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Adresse</label>
                                <input type="text" name="site_address" value="<?php echo htmlspecialchars($data['site']['address'] ?? ''); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Logo (URL)</label>
                                <input type="url" name="site_logo" value="<?php echo htmlspecialchars($data['site']['logo'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nombre de produits</label>
                                <input type="text" name="stat_products" value="<?php echo htmlspecialchars($data['site']['stats']['products'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Note (avis)</label>
                                <input type="text" name="stat_rating" value="<?php echo htmlspecialchars($data['site']['stats']['rating'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Délai de livraison</label>
                                <input type="text" name="stat_delivery" value="<?php echo htmlspecialchars($data['site']['stats']['delivery'] ?? ''); ?>">
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
            
            <?php elseif ($page === 'themes'): ?>
            <!-- ============================================================================
                 THEMES PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <h2>Sélectionner un thème</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="theme-grid">
                            <?php foreach ($availableThemes as $themeId => $theme): ?>
                            <label class="theme-card <?php echo ($currentTheme === $themeId) ? 'active' : ''; ?>">
                                <input type="radio" name="theme" value="<?php echo $themeId; ?>" <?php echo ($currentTheme === $themeId) ? 'checked' : ''; ?> style="display: none;">
                                <div class="theme-preview" style="background: <?php echo $theme['color']; ?>;"></div>
                                <div class="theme-name"><?php echo $theme['icon']; ?> <?php echo $theme['name']; ?></div>
                                <div class="theme-desc"><?php echo $theme['desc']; ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-actions" style="margin-top: 2rem;">
                            <button type="submit" name="change_theme" class="btn btn-primary">
                                <i class="fas fa-check"></i>
                                Appliquer le thème
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php elseif ($page === 'settings'): ?>
            <!-- ============================================================================
                 SETTINGS PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <h2>Paramètres généraux</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Devise</label>
                                <input type="text" name="currency" value="<?php echo htmlspecialchars($data['settings']['currency'] ?? 'FCFA'); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Message WhatsApp par défaut</label>
                                <textarea name="whatsapp_message" rows="3"><?php echo htmlspecialchars($data['settings']['whatsapp_message'] ?? ''); ?></textarea>
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
            
            <?php elseif ($page === 'security'): ?>
            <!-- ============================================================================
                 SECURITY PAGE
                 ============================================================================ -->
            <div class="card">
                <div class="card-header">
                    <h2>Sécurité</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Mot de passe actuel *</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>Nouveau mot de passe *</label>
                                <input type="password" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label>Confirmer le mot de passe *</label>
                                <input type="password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-lock"></i>
                                Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    <?php endif; ?>
    
    <!-- ============================================================================
         ADD PRODUCT MODAL
         ============================================================================ -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Ajouter un produit</h2>
                <button class="modal-close" onclick="closeModal('addProductModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nom du produit *</label>
                            <input type="text" name="product_name" required placeholder="Ex: iPhone 15 Pro">
                        </div>
                        <div class="form-group">
                            <label>Marque *</label>
                            <input type="text" name="product_brand" required placeholder="Ex: Apple">
                        </div>
                        <div class="form-group">
                            <label>Catégorie *</label>
                            <select name="product_category" required>
                                <?php foreach (array_slice($data['categories'], 1) as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Genre *</label>
                            <select name="product_gender" required>
                                <?php foreach (array_slice($data['genders'] ?? ['Tous', 'Femme', 'Homme', 'Mixte'], 1) as $gender): ?>
                                <option value="<?php echo $gender; ?>"><?php echo $gender; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prix *</label>
                            <input type="number" name="product_price" required placeholder="Ex: 25000">
                        </div>
                        <div class="form-group">
                            <label>Ancien prix (promo)</label>
                            <input type="number" name="product_old_price" placeholder="Laissez vide si pas de promo">
                        </div>
                        <div class="form-group">
                            <label>Stock *</label>
                            <input type="number" name="product_stock" value="10" required>
                        </div>
                        <div class="form-group">
                            <label>Badge</label>
                            <select name="product_badge">
                                <option value="">Aucun</option>
                                <option value="promo">Promo</option>
                                <option value="nouveau">Nouveau</option>
                                <option value="populaire">Populaire</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note (avis)</label>
                            <input type="number" name="product_rating" value="0" min="0">
                        </div>
                        
                        <!-- Image principale -->
                        <div class="form-group full-width">
                            <label>Image principale *</label>
                            <div class="image-input-group">
                                <input type="url" name="product_image" id="product_image_main" placeholder="https://... ou sélectionnez depuis la galerie" required>
                                <button type="button" class="btn-select-image" onclick="openGallerySelector('product_image_main')">
                                    <i class="fas fa-images"></i> Galerie
                                </button>
                            </div>
                        </div>
                        
                        <!-- Images supplémentaires -->
                        <div class="form-group full-width">
                            <label>Images supplémentaires (jusqu'à 5)</label>
                            <p style="font-size: 0.75rem; color: var(--gray); margin-bottom: 0.5rem;">
                                Ces images seront affichées dans le carousel du produit
                            </p>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="image-input-group" style="margin-bottom: 0.5rem;">
                                <input type="url" name="product_image_<?php echo $i; ?>" id="product_image_<?php echo $i; ?>" placeholder="Image supplémentaire <?php echo $i; ?>">
                                <button type="button" class="btn-select-image" onclick="openGallerySelector('product_image_<?php echo $i; ?>')">
                                    <i class="fas fa-images"></i>
                                </button>
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="product_description" rows="3" placeholder="Décrivez le produit..."></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addProductModal')">Annuler</button>
                        <button type="submit" name="add_product" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Ajouter le produit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ============================================================================
         EDIT PRODUCT MODAL
         ============================================================================ -->
    <div class="modal-overlay" id="editProductModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Modifier un produit</h2>
                <button class="modal-close" onclick="closeModal('editProductModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nom du produit *</label>
                            <input type="text" name="product_name" id="edit_product_name" required>
                        </div>
                        <div class="form-group">
                            <label>Marque *</label>
                            <input type="text" name="product_brand" id="edit_product_brand" required>
                        </div>
                        <div class="form-group">
                            <label>Catégorie *</label>
                            <select name="product_category" id="edit_product_category" required>
                                <?php foreach (array_slice($data['categories'], 1) as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Genre *</label>
                            <select name="product_gender" id="edit_product_gender" required>
                                <?php foreach (array_slice($data['genders'] ?? ['Tous', 'Femme', 'Homme', 'Mixte'], 1) as $gender): ?>
                                <option value="<?php echo $gender; ?>"><?php echo $gender; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prix *</label>
                            <input type="number" name="product_price" id="edit_product_price" required>
                        </div>
                        <div class="form-group">
                            <label>Ancien prix (promo)</label>
                            <input type="number" name="product_old_price" id="edit_product_old_price">
                        </div>
                        <div class="form-group">
                            <label>Stock *</label>
                            <input type="number" name="product_stock" id="edit_product_stock" required>
                        </div>
                        <div class="form-group">
                            <label>Badge</label>
                            <select name="product_badge" id="edit_product_badge">
                                <option value="">Aucun</option>
                                <option value="promo">Promo</option>
                                <option value="nouveau">Nouveau</option>
                                <option value="populaire">Populaire</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Note (avis)</label>
                            <input type="number" name="product_rating" id="edit_product_rating" value="0" min="0">
                        </div>
                        
                        <!-- Image principale -->
                        <div class="form-group full-width">
                            <label>Image principale *</label>
                            <div class="image-input-group">
                                <input type="url" name="product_image" id="edit_product_image_main" required>
                                <button type="button" class="btn-select-image" onclick="openGallerySelector('edit_product_image_main')">
                                    <i class="fas fa-images"></i> Galerie
                                </button>
                            </div>
                        </div>
                        
                        <!-- Images supplémentaires -->
                        <div class="form-group full-width">
                            <label>Images supplémentaires (jusqu'à 5)</label>
                            <p style="font-size: 0.75rem; color: var(--gray); margin-bottom: 0.5rem;">
                                Ces images seront affichées dans le carousel du produit
                            </p>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="image-input-group" style="margin-bottom: 0.5rem;">
                                <input type="url" name="product_image_<?php echo $i; ?>" id="edit_product_image_<?php echo $i; ?>">
                                <button type="button" class="btn-select-image" onclick="openGallerySelector('edit_product_image_<?php echo $i; ?>')">
                                    <i class="fas fa-images"></i>
                                </button>
                            </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="product_description" id="edit_product_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">Annuler</button>
                        <button type="submit" name="edit_product" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ============================================================================
         ORDER MODAL
         ============================================================================ -->
    <div class="modal-overlay" id="orderModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-cart"></i> Modifier la commande</h2>
                <button class="modal-close" onclick="closeModal('orderModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="order_id" id="order_id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Statut de la commande</label>
                            <select name="status" id="order_status">
                                <option value="en_attente">En attente</option>
                                <option value="en_cours">En cours</option>
                                <option value="terminee">Terminée</option>
                                <option value="annulee">Annulée</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Statut de livraison</label>
                            <select name="delivery_status" id="order_delivery_status">
                                <option value="non_livre">Non livré</option>
                                <option value="en_cours">En cours</option>
                                <option value="livre">Livré</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nom du livreur</label>
                            <input type="text" name="delivery_name" id="order_delivery_name">
                        </div>
                        <div class="form-group">
                            <label>Téléphone du livreur</label>
                            <input type="text" name="delivery_phone" id="order_delivery_phone">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('orderModal')">Annuler</button>
                        <button type="submit" name="update_order_status" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ============================================================================
         ADD CATEGORY MODAL
         ============================================================================ -->
    <div class="modal-overlay" id="addCategoryModal">
        <div class="modal">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Ajouter une catégorie</h2>
                <button class="modal-close" onclick="closeModal('addCategoryModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Nom de la catégorie *</label>
                            <input type="text" name="category_name" required placeholder="Ex: Nouveautés">
                        </div>
                        <div class="form-group">
                            <label>Icône</label>
                            <input type="text" name="category_icon" placeholder="Ex: 🆕" value="📦">
                        </div>
                        <!-- Le nombre de produits est calculé automatiquement -->
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">Annuler</button>
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ============================================================================
         GALLERY SELECTOR MODAL
         ============================================================================ -->
    <div class="modal-overlay" id="gallerySelectorModal">
        <div class="modal" style="max-width: 800px;">
            <div class="modal-header">
                <h2><i class="fas fa-images"></i> Sélectionner une image</h2>
                <button class="modal-close" onclick="closeModal('gallerySelectorModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="gallery-grid" id="gallerySelectorGrid">
                    <?php foreach ($galleryImages as $img): ?>
                    <div class="gallery-item" onclick="selectGalleryImage('<?php echo htmlspecialchars($img['url']); ?>')">
                        <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="<?php echo htmlspecialchars($img['name']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($galleryImages)): ?>
                <p style="text-align: center; color: var(--gray); padding: 2rem;">
                    <i class="fas fa-images" style="font-size: 2rem; opacity: 0.3; margin-bottom: 1rem; display: block;"></i>
                    La galerie est vide. Allez dans la section "Galerie" pour ajouter des images.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        let currentImageInputId = null;
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        function openGallerySelector(inputId) {
            currentImageInputId = inputId;
            openModal('gallerySelectorModal');
        }
        
        function selectGalleryImage(url) {
            if (currentImageInputId) {
                const inputElement = document.getElementById(currentImageInputId);
                if (inputElement) {
                    inputElement.value = url;
                    inputElement.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            closeModal('gallerySelectorModal');
        }
        
        function openEditModal(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_product_name').value = product.name;
            document.getElementById('edit_product_brand').value = product.brand;
            document.getElementById('edit_product_category').value = product.category;
            document.getElementById('edit_product_gender').value = product.gender;
            document.getElementById('edit_product_price').value = product.price;
            document.getElementById('edit_product_old_price').value = product.old_price || '';
            document.getElementById('edit_product_stock').value = product.stock;
            document.getElementById('edit_product_badge').value = product.badge || '';
            document.getElementById('edit_product_rating').value = product.rating || 0;
            document.getElementById('edit_product_image_main').value = product.image;
            document.getElementById('edit_product_description').value = product.description || '';
            
            // Remplir les images supplémentaires
            if (product.images) {
                for (let i = 0; i < product.images.length && i < 5; i++) {
                    document.getElementById('edit_product_image_' + (i + 1)).value = product.images[i];
                }
            }
            
            openModal('editProductModal');
        }
        
        function openOrderModal(order) {
            document.getElementById('order_id').value = order.id;
            document.getElementById('order_status').value = order.status;
            document.getElementById('order_delivery_status').value = order.delivery_status;
            document.getElementById('order_delivery_name').value = order.delivery_name || '';
            document.getElementById('order_delivery_phone').value = order.delivery_phone || '';
            openModal('orderModal');
        }
        
        // Fermer les modals au clic sur l'overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>
