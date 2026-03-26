<?php
/**
 * E-COMMERCE MODULAIRE - INDEX.PHP
 * 
 * Système e-commerce complet avec:
 * - Gestion des produits avec jusqu'à 5 images supplémentaires
 * - Carousel d'images fonctionnel
 * - Support de 10 thèmes CSS prédéfinis
 * - Panier avec commande WhatsApp
 * - Design responsive (PC, tablette, mobile)
 * 
 * @version 2.0
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

// Thèmes disponibles
$availableThemes = [
    'theme-1-rose' => ['name' => 'Rose Pink', 'color' => '#ec4899', 'icon' => '🌸'],
    'theme-2-bleu' => ['name' => 'Bleu Océan', 'color' => '#0ea5e9', 'icon' => '🌊'],
    'theme-3-vert' => ['name' => 'Vert Nature', 'color' => '#10b981', 'icon' => '🌿'],
    'theme-4-or' => ['name' => 'Or Luxe', 'color' => '#d4af37', 'icon' => '✨'],
    'theme-5-rouge' => ['name' => 'Rouge Passion', 'color' => '#dc2626', 'icon' => '❤️'],
    'theme-6-violet' => ['name' => 'Violet Royal', 'color' => '#7c3aed', 'icon' => '💜'],
    'theme-7-orange' => ['name' => 'Orange Énergie', 'color' => '#f97316', 'icon' => '☀️'],
    'theme-8-noir' => ['name' => 'Noir Élégant', 'color' => '#1f2937', 'icon' => '🖤'],
    'theme-9-turquoise' => ['name' => 'Turquoise Tropical', 'color' => '#14b8a6', 'icon' => '🏝️'],
    'theme-10-marron' => ['name' => 'Marron Cuir', 'color' => '#92400e', 'icon' => '🤎'],
];

// ============================================================================
// FONCTIONS DE GESTION DES DONNÉES
// ============================================================================

/**
 * Charge les données depuis le fichier JSON
 * @return array Données du site
 */
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

/**
 * Sauvegarde les données dans le fichier JSON
 * @param array $data Données à sauvegarder
 * @return bool Succès de l'opération
 */
function saveData($data) {
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Retourne les données par défaut pour une nouvelle installation
 * @return array Données par défaut
 */
function getDefaultData() {
    return [
        'site' => [
            'name' => 'Ma Boutique',
            'tagline' => 'Votre shopping en ligne',
            'description' => 'Découvrez notre sélection de produits de qualité. Livraison rapide et paiement sécurisé.',
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
            ['id' => 'nouveautes', 'name' => 'Nouveautés', 'icon' => '🆕', 'count' => 0],
            ['id' => 'promos', 'name' => 'Promos', 'icon' => '🔥', 'count' => 0],
        ],
        'genders' => ['Tous', 'Femme', 'Homme', 'Mixte'],
        'products' => [],
        'featured' => [],
        'customers' => [],
        'orders' => [],
        'settings' => [
            'currency' => 'FCFA',
            'theme' => 'theme-1-rose',
            'whatsapp_message' => 'Bonjour, je souhaite commander les articles suivants :'
        ]
    ];
}

// ============================================================================
// INITIALISATION
// ============================================================================

// Créer le fichier de données s'il n'existe pas
if (!file_exists(DATA_FILE)) {
    saveData(getDefaultData());
}

// Charger les données
$data = loadData();

// S'assurer que toutes les clés existent
if (!isset($data['customers'])) $data['customers'] = [];
if (!isset($data['orders'])) $data['orders'] = [];
if (!isset($data['settings']['theme'])) $data['settings']['theme'] = 'theme-1-rose';
if (!isset($data['settings']['currency'])) $data['settings']['currency'] = 'FCFA';

// Thème actuel
$currentTheme = $data['settings']['theme'] ?? 'theme-1-rose';

// Initialiser le panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ============================================================================
// GESTION DES ACTIONS AJAX
// ============================================================================

// Ajouter au panier
if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $productId = intval($_POST['product_id']);
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] === $productId) {
            $item['quantity']++;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = ['id' => $productId, 'quantity' => 1];
    }
    echo json_encode(['success' => true, 'cart_count' => array_sum(array_column($_SESSION['cart'], 'quantity'))]);
    exit;
}

// Supprimer du panier
if (isset($_POST['action']) && $_POST['action'] === 'remove_from_cart') {
    $productId = intval($_POST['product_id']);
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] === $productId) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    echo json_encode(['success' => true]);
    exit;
}

// Vider le panier
if (isset($_POST['action']) && $_POST['action'] === 'clear_cart') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true]);
    exit;
}

// ============================================================================
// TRAITEMENT DES COMMANDES
// ============================================================================

// Traitement du formulaire de première commande
if (isset($_POST['submit_first_order'])) {
    $customerId = 'CUST_' . time();
    $newCustomer = [
        'id' => $customerId,
        'firstname' => htmlspecialchars($_POST['firstname']),
        'lastname' => htmlspecialchars($_POST['lastname']),
        'phone' => htmlspecialchars($_POST['phone']),
        'quartier' => htmlspecialchars($_POST['quartier']),
        'created_at' => date('Y-m-d H:i:s'),
        'order_count' => 1
    ];
    
    $data['customers'][] = $newCustomer;
    $_SESSION['customer_id'] = $customerId;
    $_SESSION['customer_phone'] = $_POST['phone'];
    
    // Créer la commande
    $orderId = 'ORD_' . time();
    $cartTotal = 0;
    $orderItems = [];
    
    foreach ($_SESSION['cart'] as $item) {
        $product = array_filter($data['products'], fn($p) => $p['id'] === $item['id']);
        if ($product) {
            $product = array_values($product)[0];
            $orderItems[] = [
                'product_id' => $item['id'],
                'name' => $product['name'],
                'brand' => $product['brand'],
                'price' => $product['price'],
                'quantity' => $item['quantity']
            ];
            $cartTotal += $product['price'] * $item['quantity'];
        }
    }
    
    $newOrder = [
        'id' => $orderId,
        'customer_id' => $customerId,
        'customer_name' => $newCustomer['firstname'] . ' ' . $newCustomer['lastname'],
        'customer_phone' => $newCustomer['phone'],
        'customer_quartier' => $newCustomer['quartier'],
        'items' => $orderItems,
        'total' => $cartTotal,
        'status' => 'en_attente',
        'delivery_status' => 'non_livre',
        'delivery_name' => '',
        'delivery_phone' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $data['orders'][] = $newOrder;
    saveData($data);
    
    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = true;
    
    // Redirection WhatsApp
    $whatsappMessage = generateWhatsAppMessage($orderItems, $cartTotal, $newCustomer, $data);
    header('Location: https://wa.me/' . $data['site']['phone'] . '?text=' . urlencode($whatsappMessage));
    exit;
}

// Commande client existant
if (isset($_POST['submit_existing_order'])) {
    $customerId = $_SESSION['customer_id'];
    $customer = null;
    foreach ($data['customers'] as $c) {
        if ($c['id'] === $customerId) {
            $customer = $c;
            break;
        }
    }
    
    foreach ($data['customers'] as &$c) {
        if ($c['id'] === $customerId) {
            $c['order_count']++;
            break;
        }
    }
    
    $orderId = 'ORD_' . time();
    $cartTotal = 0;
    $orderItems = [];
    
    foreach ($_SESSION['cart'] as $item) {
        $product = array_filter($data['products'], fn($p) => $p['id'] === $item['id']);
        if ($product) {
            $product = array_values($product)[0];
            $orderItems[] = [
                'product_id' => $item['id'],
                'name' => $product['name'],
                'brand' => $product['brand'],
                'price' => $product['price'],
                'quantity' => $item['quantity']
            ];
            $cartTotal += $product['price'] * $item['quantity'];
        }
    }
    
    $newOrder = [
        'id' => $orderId,
        'customer_id' => $customerId,
        'customer_name' => $customer['firstname'] . ' ' . $customer['lastname'],
        'customer_phone' => $customer['phone'],
        'customer_quartier' => $customer['quartier'],
        'items' => $orderItems,
        'total' => $cartTotal,
        'status' => 'en_attente',
        'delivery_status' => 'non_livre',
        'delivery_name' => '',
        'delivery_phone' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $data['orders'][] = $newOrder;
    saveData($data);
    
    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = true;
    
    $whatsappMessage = generateWhatsAppMessage($orderItems, $cartTotal, $customer, $data);
    header('Location: https://wa.me/' . $data['site']['phone'] . '?text=' . urlencode($whatsappMessage));
    exit;
}

/**
 * Génère le message WhatsApp pour une commande
 */
function generateWhatsAppMessage($items, $total, $customer, $data) {
    $message = $data['settings']['whatsapp_message'] . "\n\n";
    $message .= "*Client:* " . $customer['firstname'] . ' ' . $customer['lastname'] . "\n";
    $message .= "*Téléphone:* " . $customer['phone'] . "\n";
    $message .= "*Quartier:* " . $customer['quartier'] . "\n\n";
    $message .= "*Articles commandés:*\n";
    foreach ($items as $item) {
        $message .= "• " . $item['name'] . " (" . $item['brand'] . ")\n";
        $message .= "  " . $item['quantity'] . " x " . formatPriceStatic($item['price'], $data) . "\n\n";
    }
    $message .= "*Total: " . formatPriceStatic($total, $data) . "*";
    return $message;
}

// ============================================================================
// FILTRAGE DES PRODUITS
// ============================================================================

$selectedCategory = $_GET['category'] ?? 'tous';
$selectedGender = $_GET['gender'] ?? 'Tous';
$searchQuery = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'default';

// Filtrer les produits
$filteredProducts = $data['products'];

if ($selectedCategory !== 'tous') {
    $filteredProducts = array_filter($filteredProducts, function($p) use ($selectedCategory) {
        return $p['category'] === $selectedCategory;
    });
}

if ($selectedGender !== 'Tous') {
    $filteredProducts = array_filter($filteredProducts, function($p) use ($selectedGender) {
        return $p['gender'] === $selectedGender || $p['gender'] === 'Mixte';
    });
}

if ($searchQuery) {
    $filteredProducts = array_filter($filteredProducts, function($p) use ($searchQuery) {
        return stripos($p['name'], $searchQuery) !== false || 
               stripos($p['brand'], $searchQuery) !== false;
    });
}

// Trier
switch ($sort) {
    case 'price_asc':
        usort($filteredProducts, fn($a, $b) => $a['price'] - $b['price']);
        break;
    case 'price_desc':
        usort($filteredProducts, fn($a, $b) => $b['price'] - $a['price']);
        break;
    case 'rating':
        usort($filteredProducts, fn($a, $b) => $b['rating'] - $a['rating']);
        break;
}

// ============================================================================
// CALCUL DU PANIER
// ============================================================================

$cartTotal = 0;
$cartItems = [];
foreach ($_SESSION['cart'] as $item) {
    $product = array_filter($data['products'], fn($p) => $p['id'] === $item['id']);
    if ($product) {
        $product = array_values($product)[0];
        $cartItems[] = array_merge($product, ['quantity' => $item['quantity']]);
        $cartTotal += $product['price'] * $item['quantity'];
    }
}

$cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
$isFirstOrder = !isset($_SESSION['customer_id']) && $cartCount > 0;

// Produits en vedette
$featuredProducts = array_filter($data['products'], function($p) use ($data) {
    return in_array($p['id'], $data['featured'] ?? []);
});

// Message de succès
$orderSuccess = $_SESSION['order_success'] ?? false;
unset($_SESSION['order_success']);

// ============================================================================
// FONCTIONS UTILITAIRES
// ============================================================================

function formatPrice($price) {
    global $data;
    return number_format($price, 0, ',', ' ') . ' ' . $data['settings']['currency'];
}

function formatPriceStatic($price, $data) {
    return number_format($price, 0, ',', ' ') . ' ' . ($data['settings']['currency'] ?? 'FCFA');
}

function getDiscount($oldPrice, $price) {
    if (!$oldPrice) return 0;
    return round((($oldPrice - $price) / $oldPrice) * 100);
}

// ============================================================================
// RÉCUPÉRATION DES IMAGES DE LA GALERIE
// ============================================================================

function getGalleryImages() {
    $images = [];
    if (is_dir(GALLERY_DIR)) {
        $files = glob(GALLERY_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            $images[] = basename($file);
        }
    }
    return $images;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($data['site']['name']); ?> — <?php echo htmlspecialchars($data['site']['tagline']); ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($currentTheme); ?>.css">
    
    <style>
        /* ============================================================================
           STYLES DE BASE
           ============================================================================ */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
            line-height: 1.5;
        }
        
        /* ============================================================================
           HEADER
           ============================================================================ */
        
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        
        .logo-img {
            height: 85px;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s;
        }
        
        .logo:hover .logo-img {
            transform: scale(1.02);
        }
        
        .nav {
            display: none;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 42px;
            height: 42px;
            border: none;
            background: white;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.2s;
            position: relative;
            font-size: 1rem;
        }
        
        .btn-icon:hover {
            color: var(--primary);
            transform: scale(1.05);
        }
        
        .btn-icon .badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 20px;
            height: 20px;
            background: var(--primary);
            color: white;
            font-size: 0.65rem;
            font-weight: 600;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }
        
        .btn-whatsapp {
            padding: 0.6rem 1rem;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            text-decoration: none;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        }
        
        /* ============================================================================
           HERO SECTION
           ============================================================================ */
        
        .hero {
            padding-top: 90px;
            min-height: auto;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -50%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--shadow-color) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .hero::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -30%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.12) 0%, transparent 70%);
            animation: float 25s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
        
        .hero-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem 1rem;
            position: relative;
            z-index: 1;
        }
        
        .hero-content {
            text-align: center;
        }
        
        .hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            line-height: 1.2;
            margin-bottom: 0.75rem;
            color: var(--dark);
        }
        
        .hero-content h1 span {
            display: block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-content p {
            font-size: 0.9rem;
            color: var(--gray);
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .stat-label {
            font-size: 0.6rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .hero-images {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            max-width: 280px;
            margin: 0 auto;
        }
        
        .hero-image {
            aspect-ratio: 3/4;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* ============================================================================
           PRODUCTS SECTION
           ============================================================================ */
        
        .products-section {
            padding: 1.5rem 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            margin-bottom: 0.25rem;
        }
        
        .section-header p {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        /* Categories */
        .categories {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .categories::-webkit-scrollbar {
            display: none;
        }
        
        .category-btn {
            padding: 0.6rem 0.875rem;
            background: white;
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            text-decoration: none;
            color: var(--dark);
            white-space: nowrap;
            min-width: 65px;
        }
        
        .category-btn:hover, .category-btn.active {
            border-color: var(--primary);
        }
        
        .category-btn .icon {
            font-size: 1.1rem;
        }
        
        .category-btn .name {
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .category-btn .count {
            font-size: 0.6rem;
            color: var(--gray);
        }
        
        /* Filters */
        .filters {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            gap: 0.35rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .filter-group::-webkit-scrollbar {
            display: none;
        }
        
        .filter-btn {
            padding: 0.35rem 0.75rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 9999px;
            cursor: pointer;
            font-size: 0.75rem;
            transition: all 0.2s;
            white-space: nowrap;
            text-decoration: none;
            color: var(--dark);
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .search-sort {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .search-box {
            position: relative;
            flex: 1;
        }
        
        .search-box input {
            padding: 0.55rem 1rem 0.55rem 2.25rem;
            border: 1px solid var(--border);
            border-radius: 9999px;
            width: 100%;
            font-size: 0.85rem;
        }
        
        .search-box i {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .sort-select {
            padding: 0.55rem 0.6rem;
            border: 1px solid var(--border);
            border-radius: 9999px;
            font-size: 0.75rem;
            background: white;
            cursor: pointer;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.6rem;
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
        }
        
        .product-card:active {
            transform: scale(0.98);
        }
        
        .product-badge {
            position: absolute;
            top: 6px;
            left: 6px;
            padding: 0.2rem 0.4rem;
            border-radius: 9999px;
            font-size: 0.55rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 2;
        }
        
        .badge-promo {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .badge-nouveau {
            background: linear-gradient(135deg, var(--secondary), #7c3aed);
            color: white;
        }
        
        .badge-populaire {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .product-image {
            aspect-ratio: 1;
            position: relative;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-gender {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 24px;
            height: 24px;
            background: rgba(255,255,255,0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            backdrop-filter: blur(4px);
        }
        
        .product-info {
            padding: 0.6rem;
        }
        
        .product-brand {
            font-size: 0.6rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.1rem;
        }
        
        .product-category {
            font-size: 0.55rem;
            color: var(--primary);
            margin-bottom: 0.2rem;
        }
        
        .product-name {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
            color: var(--dark);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-bottom: 0.3rem;
        }
        
        .stars {
            color: #fbbf24;
            font-size: 0.6rem;
        }
        
        .rating-count {
            font-size: 0.6rem;
            color: var(--gray);
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-price {
            display: flex;
            flex-direction: column;
        }
        
        .current-price {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .old-price {
            font-size: 0.65rem;
            color: var(--gray);
            text-decoration: line-through;
        }
        
        .btn-add-cart {
            width: 28px;
            height: 28px;
            border: none;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 0.7rem;
        }
        
        .btn-add-cart:active {
            transform: scale(0.9);
        }

        /* ============================================================================
           PRODUCT MODAL AVEC CAROUSEL
           ============================================================================ */
        
        .product-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            display: none;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .product-modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        
        .product-modal {
            background: white;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform 0.3s ease-out;
        }
        
        .product-modal-overlay.active .product-modal {
            transform: translateY(0);
        }
        
        .product-modal-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.95);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            z-index: 10;
            backdrop-filter: blur(4px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Carousel Styles */
        .carousel-container {
            position: relative;
            width: 100%;
            background: #f9fafb;
        }
        
        .carousel-main {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            overflow: hidden;
        }
        
        .carousel-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.3s;
        }
        
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: var(--dark);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            transition: all 0.2s;
            z-index: 5;
        }
        
        .carousel-nav:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-nav.prev {
            left: 10px;
        }
        
        .carousel-nav.next {
            right: 10px;
        }
        
        .carousel-nav:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .carousel-thumbnails {
            display: flex;
            gap: 8px;
            padding: 12px;
            overflow-x: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            background: white;
        }
        
        .carousel-thumbnails::-webkit-scrollbar {
            display: none;
        }
        
        .carousel-thumb {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
            opacity: 0.6;
        }
        
        .carousel-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .carousel-thumb.active {
            border-color: var(--primary);
            opacity: 1;
        }
        
        .carousel-thumb:hover {
            opacity: 1;
        }
        
        .carousel-counter {
            position: absolute;
            bottom: 15px;
            right: 15px;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            z-index: 5;
        }
        
        .product-modal-content {
            padding: 1rem;
        }
        
        .product-modal-header {
            margin-bottom: 0.75rem;
        }
        
        .product-modal-brand {
            font-size: 0.7rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .product-modal-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0.15rem 0;
            color: var(--dark);
        }
        
        .product-modal-category {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .product-modal-description {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        
        .product-modal-footer {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border);
        }
        
        .product-modal-price {
            flex: 1;
        }
        
        .product-modal-price .current {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .product-modal-price .old {
            font-size: 0.9rem;
            color: var(--gray);
            text-decoration: line-through;
            margin-left: 0.5rem;
        }
        
        .btn-add-modal {
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }
        
        .btn-add-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        /* ============================================================================
           CART SIDEBAR
           ============================================================================ */
        
        .cart-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .cart-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .cart-sidebar {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            max-width: 400px;
            height: 100vh;
            background: white;
            z-index: 2001;
            transition: right 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .cart-sidebar.active {
            right: 0;
        }
        
        .cart-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .cart-header h3 {
            font-size: 1rem;
        }
        
        .cart-header-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cart-close {
            width: 32px;
            height: 32px;
            border: none;
            background: var(--light);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .cart-close:hover {
            background: var(--border);
        }
        
        .btn-clear-cart {
            padding: 0.4rem 0.75rem;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
        }
        
        .btn-clear-cart:hover {
            background: #fecaca;
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 0.875rem;
        }
        
        .cart-empty {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--gray);
        }
        
        .cart-empty i {
            font-size: 3.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.3;
        }
        
        .cart-item {
            display: flex;
            gap: 0.6rem;
            padding: 0.75rem;
            background: var(--light);
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }
        
        .cart-item-image {
            width: 56px;
            height: 56px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-info {
            flex: 1;
            min-width: 0;
        }
        
        .cart-item-name {
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .cart-item-brand {
            font-size: 0.65rem;
            color: var(--gray);
            margin-bottom: 0.2rem;
        }
        
        .cart-item-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.8rem;
        }
        
        .cart-item-qty {
            font-size: 0.7rem;
            color: var(--gray);
        }
        
        .cart-item-remove {
            width: 26px;
            height: 26px;
            border: none;
            background: transparent;
            color: var(--gray);
            cursor: pointer;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        
        .cart-item-remove:hover {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .cart-footer {
            padding: 1rem;
            border-top: 1px solid var(--border);
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .btn-checkout {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        }
        
        .btn-checkout:active {
            transform: scale(0.98);
        }

        /* ============================================================================
           ORDER FORM MODAL
           ============================================================================ */
        
        .order-form-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 3000;
            display: none;
            align-items: flex-end;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .order-form-overlay.active {
            display: flex;
            opacity: 1;
        }
        
        .order-form-modal {
            background: white;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(100%);
            transition: transform 0.3s ease-out;
        }
        
        .order-form-overlay.active .order-form-modal {
            transform: translateY(0);
        }
        
        .order-form-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }
        
        .order-form-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            margin-bottom: 0.25rem;
        }
        
        .order-form-header p {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .order-form-body {
            padding: 1.25rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .form-group label .required {
            color: var(--error);
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.2s;
            font-family: inherit;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        
        .order-summary {
            background: var(--light);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
        }
        
        .order-summary h4 {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            margin-bottom: 0.4rem;
            color: var(--gray);
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border);
            font-weight: 700;
            font-size: 1rem;
        }
        
        .btn-submit-order {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-submit-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        }
        
        .btn-submit-order:active {
            transform: scale(0.98);
        }
        
        .customer-info-box {
            background: var(--light);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
        }
        
        .customer-info-box p {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .customer-info-box p:last-child {
            margin-bottom: 0;
        }
        
        .customer-info-box strong {
            color: var(--dark);
        }
        
        /* ============================================================================
           SUCCESS MODAL
           ============================================================================ */
        
        .success-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 4000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .success-overlay.active {
            display: flex;
        }
        
        .success-modal {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            max-width: 320px;
            animation: scaleIn 0.3s ease-out;
        }
        
        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        
        .success-modal i {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1rem;
        }
        
        .success-modal h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .success-modal p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-whatsapp-success {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-whatsapp-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
        }
        
        /* ============================================================================
           FOOTER
           ============================================================================ */
        
        .footer {
            background: var(--dark);
            color: white;
            padding: 1.5rem 1rem;
            margin-top: 1.5rem;
        }
        
        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            text-align: center;
        }
        
        .footer-brand {
            margin-bottom: 1rem;
        }
        
        .footer-brand img {
            height: 70px;
            margin-bottom: 0.5rem;
        }
        
        .footer-brand h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .footer-brand p {
            color: #9ca3af;
            font-size: 0.75rem;
            line-height: 1.4;
        }
        
        .footer-contact {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-bottom: 1rem;
        }
        
        .footer-contact a, .footer-contact p {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            color: #9ca3af;
            text-decoration: none;
            font-size: 0.8rem;
        }
        
        .footer-contact a:hover {
            color: white;
        }
        
        .footer-social {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .footer-social a {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .footer-social a:hover {
            background: var(--primary);
            transform: translateY(-2px);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 0.875rem;
        }
        
        .footer-bottom p {
            color: #9ca3af;
            font-size: 0.7rem;
        }
        
        /* ============================================================================
           TOAST NOTIFICATION
           ============================================================================ */
        
        .toast {
            position: fixed;
            bottom: 1.25rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--dark);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            z-index: 5000;
            opacity: 0;
            transition: all 0.3s;
            font-size: 0.85rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        
        .toast i {
            color: var(--success);
        }
        
        /* ============================================================================
           RESPONSIVE - TABLET
           ============================================================================ */
        
        @media (min-width: 640px) {
            .hero-content h1 {
                font-size: 2.2rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.875rem;
            }
            
            .hero-images {
                max-width: 350px;
            }
        }
        
        /* ============================================================================
           RESPONSIVE - DESKTOP
           ============================================================================ */
        
        @media (min-width: 768px) {
            .header-container {
                padding: 0.75rem 2rem;
            }
            
            .logo-img {
                height: 105px;
            }
            
            .nav {
                display: flex;
                align-items: center;
                gap: 0.25rem;
            }
            
            .nav-link {
                padding: 0.5rem 1rem;
                text-decoration: none;
                color: var(--gray);
                font-size: 0.875rem;
                font-weight: 500;
                border-radius: 9999px;
                transition: all 0.2s;
            }
            
            .nav-link:hover, .nav-link.active {
                color: var(--primary);
                background: rgba(236, 72, 153, 0.1);
            }
            
            .hero-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 2.5rem;
                align-items: center;
                padding: 3rem 2rem;
            }
            
            .hero-content {
                text-align: left;
            }
            
            .hero-content h1 {
                font-size: 2.8rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            .hero-stats {
                justify-content: flex-start;
            }
            
            .hero-images {
                max-width: none;
                transform: perspective(1000px) rotateY(-5deg);
            }
            
            .hero-image {
                border-radius: 14px;
            }
            
            .products-section {
                padding: 3rem 2rem;
            }
            
            .section-header h2 {
                font-size: 1.8rem;
            }
            
            .categories {
                justify-content: center;
                gap: 0.875rem;
            }
            
            .category-btn {
                padding: 0.875rem 1.25rem;
                min-width: 80px;
            }
            
            .filters {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 1.25rem;
            }
            
            .product-card:hover {
                transform: translateY(-6px);
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            }
            
            .product-modal, .order-form-modal {
                max-width: 600px;
                border-radius: 20px;
                max-height: 90vh;
                margin: auto;
            }
            
            .product-modal-overlay, .order-form-overlay {
                align-items: center;
                padding: 2rem;
            }
            
            .product-modal-overlay .product-modal,
            .order-form-overlay .order-form-modal {
                transform: scale(0.95);
                opacity: 0;
            }
            
            .product-modal-overlay.active .product-modal,
            .order-form-overlay.active .order-form-modal {
                transform: scale(1);
                opacity: 1;
            }
            
            .carousel-main {
                aspect-ratio: 4/3;
            }
            
            .carousel-thumb {
                width: 70px;
                height: 70px;
            }
            
            .footer {
                padding: 2.5rem 2rem;
            }
            
            .footer-container {
                display: grid;
                grid-template-columns: 1.5fr 1fr 1fr;
                gap: 2rem;
                text-align: left;
            }
            
            .footer-brand {
                text-align: left;
            }
            
            .footer-contact a, .footer-contact p {
                justify-content: flex-start;
            }
            
            .footer-social {
                justify-content: flex-start;
            }
        }
        
        @media (min-width: 1024px) {
            .hero-content h1 {
                font-size: 3.2rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Hide mobile elements on desktop */
        @media (min-width: 768px) {
            .hide-desktop {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .hide-mobile {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- ============================================================================
         HEADER
         ============================================================================ -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <img src="<?php echo htmlspecialchars($data['site']['logo'] ?? 'logo.png'); ?>" alt="<?php echo htmlspecialchars($data['site']['name']); ?>" class="logo-img">
            </a>
            
            <nav class="nav">
                <a href="index.php" class="nav-link active">Accueil</a>
                <a href="?category=tous" class="nav-link">Produits</a>
                <?php if (count($data['categories']) > 3): ?>
                <a href="?category=<?php echo $data['categories'][3]['id']; ?>" class="nav-link"><?php echo $data['categories'][3]['name']; ?></a>
                <?php endif; ?>
            </nav>
            
            <div class="header-actions">
                <button class="btn-icon" onclick="toggleCart()" aria-label="Panier">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="badge" id="cart-badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </button>
                <a href="https://wa.me/<?php echo $data['site']['phone']; ?>" class="btn-whatsapp" target="_blank">
                    <i class="fab fa-whatsapp"></i>
                    <span class="hide-mobile">Commander</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ============================================================================
         HERO SECTION
         ============================================================================ -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>
                    <span><?php echo htmlspecialchars($data['site']['name']); ?></span>
                    <span><?php echo htmlspecialchars($data['site']['tagline']); ?></span>
                </h1>
                <p><?php echo htmlspecialchars($data['site']['description']); ?></p>
                
                <div class="hero-stats">
                    <div class="stat">
                        <div class="stat-value"><?php echo $data['site']['stats']['products']; ?></div>
                        <div class="stat-label">Produits</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo $data['site']['stats']['rating']; ?>★</div>
                        <div class="stat-label">Note</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo $data['site']['stats']['delivery']; ?></div>
                        <div class="stat-label">Livraison</div>
                    </div>
                </div>
            </div>
            
            <div class="hero-images">
                <?php 
                $featured = array_slice(array_values($featuredProducts), 0, 3);
                foreach ($featured as $product): 
                ?>
                <div class="hero-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================================================================
         PRODUCTS SECTION
         ============================================================================ -->
    <section class="products-section" id="products">
        <div class="section-header">
            <h2>Nos Produits</h2>
            <p>Découvrez notre sélection premium</p>
        </div>
        
        <!-- Categories -->
        <div class="categories">
            <?php foreach ($data['categories'] as $cat): ?>
            <a href="?category=<?php echo $cat['id']; ?>" class="category-btn <?php echo $selectedCategory === $cat['id'] ? 'active' : ''; ?>">
                <span class="icon"><?php echo $cat['icon']; ?></span>
                <span class="name"><?php echo $cat['name']; ?></span>
                <span class="count"><?php echo $cat['count']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <div class="filter-group">
                <?php foreach ($data['genders'] as $gender): ?>
                <a href="?category=<?php echo $selectedCategory; ?>&gender=<?php echo $gender; ?>&search=<?php echo urlencode($searchQuery); ?>&sort=<?php echo $sort; ?>" 
                   class="filter-btn <?php echo $selectedGender === $gender ? 'active' : ''; ?>">
                    <?php echo $gender; ?>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div class="search-sort">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Rechercher..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                           onkeypress="if(event.key==='Enter') window.location.href='?category=<?php echo $selectedCategory; ?>&gender=<?php echo $selectedGender; ?>&sort=<?php echo $sort; ?>&search='+this.value">
                </div>
                <select class="sort-select" onchange="window.location.href='?category=<?php echo $selectedCategory; ?>&gender=<?php echo $selectedGender; ?>&search=<?php echo urlencode($searchQuery); ?>&sort='+this.value">
                    <option value="default" <?php echo $sort === 'default' ? 'selected' : ''; ?>>Trier</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Prix ↑</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Prix ↓</option>
                    <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Notés</option>
                </select>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="products-grid">
            <?php foreach ($filteredProducts as $product): 
                $discount = getDiscount($product['old_price'], $product['price']);
                // Préparer les images pour le carousel
                $productImages = [$product['image']];
                if (!empty($product['images']) && is_array($product['images'])) {
                    $productImages = array_merge($productImages, $product['images']);
                }
                $productImagesJson = htmlspecialchars(json_encode($productImages));
            ?>
            <div class="product-card" onclick="openProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>, <?php echo $productImagesJson; ?>)">
                <?php if ($product['badge']): ?>
                <span class="product-badge badge-<?php echo $product['badge']; ?>">
                    <?php 
                    echo match($product['badge']) {
                        'promo' => '-' . $discount . '%',
                        'nouveau' => 'Nouveau',
                        'populaire' => '⭐ Pop',
                        default => ''
                    };
                    ?>
                </span>
                <?php endif; ?>
                
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                    <span class="product-gender" title="<?php echo $product['gender']; ?>">
                        <?php 
                        echo match($product['gender']) {
                            'Femme' => '👩',
                            'Homme' => '👨',
                            'Mixte' => '⚥',
                            default => '👤'
                        };
                        ?>
                    </span>
                </div>
                
                <div class="product-info">
                    <div class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></div>
                    <div class="product-category"><?php echo ucfirst($product['category']); ?></div>
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                    
                    <div class="product-rating">
                        <span class="stars">★★★★★</span>
                        <span class="rating-count">(<?php echo $product['rating']; ?>)</span>
                    </div>
                    
                    <div class="product-footer">
                        <div class="product-price">
                            <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                            <?php if ($product['old_price']): ?>
                            <span class="old-price"><?php echo formatPrice($product['old_price']); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="btn-add-cart" onclick="event.stopPropagation(); addToCart(<?php echo $product['id']; ?>)" aria-label="Ajouter au panier">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($filteredProducts)): ?>
        <div style="text-align: center; padding: 3rem; color: var(--gray);">
            <i class="fas fa-search" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
            <p>Aucun produit trouvé</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- ============================================================================
         PRODUCT MODAL AVEC CAROUSEL
         ============================================================================ -->
    <div class="product-modal-overlay" id="product-modal" onclick="closeProductModal(event)">
        <div class="product-modal" onclick="event.stopPropagation()">
            <button class="product-modal-close" onclick="closeProductModal()" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Carousel Container -->
            <div class="carousel-container">
                <div class="carousel-main">
                    <img id="modal-carousel-image" src="" alt="">
                    <span class="carousel-counter" id="carousel-counter">1 / 1</span>
                    <button class="carousel-nav prev" onclick="changeCarouselImage(-1)" aria-label="Image précédente">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-nav next" onclick="changeCarouselImage(1)" aria-label="Image suivante">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div class="carousel-thumbnails" id="carousel-thumbnails">
                    <!-- Thumbnails will be inserted here by JavaScript -->
                </div>
            </div>
            
            <div class="product-modal-content">
                <div class="product-modal-header">
                    <div class="product-modal-brand" id="modal-brand"></div>
                    <h2 class="product-modal-name" id="modal-name"></h2>
                    <span class="product-modal-category" id="modal-category"></span>
                </div>
                <p class="product-modal-description" id="modal-description"></p>
                <div class="product-modal-footer">
                    <div class="product-modal-price">
                        <span class="current" id="modal-price"></span>
                        <span class="old" id="modal-old-price"></span>
                    </div>
                    <button class="btn-add-modal" id="modal-add-btn">
                        <i class="fas fa-shopping-bag"></i>
                        Ajouter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================================
         FOOTER
         ============================================================================ -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <img src="<?php echo htmlspecialchars($data['site']['logo'] ?? 'logo.png'); ?>" alt="<?php echo htmlspecialchars($data['site']['name']); ?>">
                <h3><?php echo htmlspecialchars($data['site']['name']); ?></h3>
                <p><?php echo htmlspecialchars($data['site']['description']); ?></p>
            </div>
            
            <div class="footer-contact">
                <a href="https://wa.me/<?php echo $data['site']['phone']; ?>" target="_blank">
                    <i class="fab fa-whatsapp"></i> +<?php echo $data['site']['phone']; ?>
                </a>
                <p><i class="fas fa-envelope"></i> <?php echo $data['site']['email']; ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo $data['site']['address']; ?></p>
            </div>
            
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($data['site']['name']); ?> - Tous droits réservés</p>
        </div>
    </footer>

    <!-- ============================================================================
         CART SIDEBAR
         ============================================================================ -->
    <div class="cart-overlay" id="cart-overlay" onclick="toggleCart()"></div>
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3>Mon Panier</h3>
            <div class="cart-header-actions">
                <?php if ($cartCount > 0): ?>
                <button class="btn-clear-cart" onclick="clearCart()">
                    <i class="fas fa-trash-alt"></i>
                    Vider
                </button>
                <?php endif; ?>
                <button class="cart-close" onclick="toggleCart()" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="cart-items" id="cart-items">
            <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <i class="fas fa-shopping-bag"></i>
                <p>Votre panier est vide</p>
            </div>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                    <div class="cart-item-image">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="" loading="lazy">
                    </div>
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="cart-item-brand"><?php echo htmlspecialchars($item['brand']); ?></div>
                        <div class="cart-item-price"><?php echo formatPrice($item['price']); ?></div>
                        <div class="cart-item-qty">Qté: <?php echo $item['quantity']; ?></div>
                    </div>
                    <button class="cart-item-remove" onclick="event.stopPropagation(); removeFromCart(<?php echo $item['id']; ?>)" aria-label="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($cartItems)): ?>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span><?php echo formatPrice($cartTotal); ?></span>
            </div>
            <button class="btn-checkout" onclick="proceedToCheckout()">
                <i class="fab fa-whatsapp"></i>
                Valider ma commande
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================================
         ORDER FORM MODAL (First Order)
         ============================================================================ -->
    <div class="order-form-overlay" id="order-form-modal">
        <div class="order-form-modal">
            <div class="order-form-header">
                <h2>Finaliser votre commande</h2>
                <p>Veuillez remplir vos informations</p>
            </div>
            <div class="order-form-body">
                <form method="POST" id="first-order-form">
                    <div class="order-summary">
                        <h4>Récapitulatif</h4>
                        <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="order-total">
                            <span>Total</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Prénom <span class="required">*</span></label>
                            <input type="text" name="firstname" required placeholder="Votre prénom">
                        </div>
                        <div class="form-group">
                            <label>Nom <span class="required">*</span></label>
                            <input type="text" name="lastname" required placeholder="Votre nom">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Numéro WhatsApp <span class="required">*</span></label>
                        <input type="tel" name="phone" required placeholder="228XXXXXXXX">
                    </div>
                    
                    <div class="form-group">
                        <label>Quartier de résidence <span class="required">*</span></label>
                        <input type="text" name="quartier" required placeholder="Ex: Adidogomé, Tokoin...">
                    </div>
                    
                    <button type="submit" name="submit_first_order" class="btn-submit-order">
                        <i class="fab fa-whatsapp"></i>
                        Confirmer et envoyer sur WhatsApp
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================================
         EXISTING CUSTOMER ORDER FORM
         ============================================================================ -->
    <?php if (isset($_SESSION['customer_id'])): 
        $existingCustomer = null;
        foreach ($data['customers'] as $c) {
            if ($c['id'] === $_SESSION['customer_id']) {
                $existingCustomer = $c;
                break;
            }
        }
    ?>
    <div class="order-form-overlay" id="existing-order-modal">
        <div class="order-form-modal">
            <div class="order-form-header">
                <h2>Confirmer votre commande</h2>
                <p>Heureux de vous revoir <?php echo htmlspecialchars($existingCustomer['firstname']); ?> !</p>
            </div>
            <div class="order-form-body">
                <form method="POST" id="existing-order-form">
                    <div class="order-summary">
                        <h4>Récapitulatif</h4>
                        <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                            <span><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="order-total">
                            <span>Total</span>
                            <span><?php echo formatPrice($cartTotal); ?></span>
                        </div>
                    </div>
                    
                    <div class="customer-info-box">
                        <p><i class="fas fa-user"></i> <strong>Client:</strong> <?php echo htmlspecialchars($existingCustomer['firstname'] . ' ' . $existingCustomer['lastname']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($existingCustomer['phone']); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($existingCustomer['quartier']); ?></p>
                    </div>
                    
                    <button type="submit" name="submit_existing_order" class="btn-submit-order">
                        <i class="fab fa-whatsapp"></i>
                        Confirmer et envoyer sur WhatsApp
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================================
         SUCCESS MODAL
         ============================================================================ -->
    <div class="success-overlay <?php echo $orderSuccess ? 'active' : ''; ?>" id="success-modal">
        <div class="success-modal">
            <i class="fas fa-check-circle"></i>
            <h3>Commande envoyée !</h3>
            <p>Votre commande a été transmise. Nous vous contacterons bientôt sur WhatsApp.</p>
            <a href="https://wa.me/<?php echo $data['site']['phone']; ?>" class="btn-whatsapp-success" target="_blank">
                <i class="fab fa-whatsapp"></i>
                Ouvrir WhatsApp
            </a>
        </div>
    </div>

    <!-- ============================================================================
         TOAST NOTIFICATION
         ============================================================================ -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message">Produit ajouté au panier</span>
    </div>

    <!-- ============================================================================
         JAVASCRIPT
         ============================================================================ -->
    <script>
        // ============================================================================
        // VARIABLES GLOBALES
        // ============================================================================
        let currentProduct = null;
        let currentProductImages = [];
        let currentImageIndex = 0;
        let isFirstOrder = <?php echo $isFirstOrder ? 'true' : 'false'; ?>;
        let hasCustomerId = <?php echo isset($_SESSION['customer_id']) ? 'true' : 'false'; ?>;
        
        // ============================================================================
        // GESTION DU PANIER
        // ============================================================================
        
        /**
         * Affiche/masque le panier latéral
         */
        function toggleCart() {
            document.getElementById('cart-overlay').classList.toggle('active');
            document.getElementById('cart-sidebar').classList.toggle('active');
        }
        
        /**
         * Affiche une notification toast
         * @param {string} message - Message à afficher
         */
        function showToast(message) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }
        
        /**
         * Ajoute un produit au panier
         * @param {number} productId - ID du produit
         */
        function addToCart(productId) {
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add_to_cart&product_id=' + productId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Produit ajouté au panier');
                    updateCartBadge(data.cart_count);
                    setTimeout(() => location.reload(), 400);
                }
            })
            .catch(err => {
                console.error('Erreur:', err);
                showToast('Erreur lors de l\'ajout');
            });
        }
        
        /**
         * Supprime un produit du panier
         * @param {number} productId - ID du produit
         */
        function removeFromCart(productId) {
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=remove_from_cart&product_id=' + productId
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(err => {
                console.error('Erreur:', err);
                showToast('Erreur lors de la suppression');
            });
        }
        
        /**
         * Vide le panier complet
         */
        function clearCart() {
            if (confirm('Êtes-vous sûr de vouloir vider votre panier ?')) {
                fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=clear_cart'
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Panier vidé');
                        setTimeout(() => location.reload(), 400);
                    }
                })
                .catch(err => {
                    console.error('Erreur:', err);
                    showToast('Erreur lors du vidage');
                });
            }
        }
        
        /**
         * Met à jour le badge du panier
         * @param {number} count - Nombre d'articles
         */
        function updateCartBadge(count) {
            let badge = document.querySelector('#cart-badge');
            if (!badge && count > 0) {
                badge = document.createElement('span');
                badge.className = 'badge';
                badge.id = 'cart-badge';
                document.querySelector('.btn-icon').appendChild(badge);
            }
            if (badge) {
                badge.textContent = count;
                if (count === 0) badge.remove();
            }
        }
        
        // ============================================================================
        // CAROUSEL D'IMAGES
        // ============================================================================
        
        /**
         * Ouvre le modal produit avec le carousel
         * @param {Object} product - Données du produit
         * @param {Array} images - Tableau d'URLs d'images
         */
        function openProductModal(product, images) {
            currentProduct = product;
            currentProductImages = images && images.length > 0 ? images : [product.image];
            currentImageIndex = 0;
            
            // Remplir les informations du produit
            document.getElementById('modal-brand').textContent = product.brand;
            document.getElementById('modal-name').textContent = product.name;
            document.getElementById('modal-category').textContent = product.category;
            
            // Description
            const descriptionEl = document.getElementById('modal-description');
            const description = product.description || product.desc || '';
            descriptionEl.textContent = description ? description : 'Aucune description disponible pour ce produit.';
            
            // Prix
            document.getElementById('modal-price').textContent = formatPrice(product.price);
            
            const oldPriceEl = document.getElementById('modal-old-price');
            if (product.old_price) {
                oldPriceEl.textContent = formatPrice(product.old_price);
                oldPriceEl.style.display = 'inline';
            } else {
                oldPriceEl.style.display = 'none';
            }
            
            // Bouton d'ajout au panier
            document.getElementById('modal-add-btn').onclick = function() {
                addToCart(product.id);
                closeProductModal();
            };
            
            // Initialiser le carousel
            updateCarousel();
            generateThumbnails();
            
            // Afficher le modal
            document.getElementById('product-modal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        /**
         * Ferme le modal produit
         * @param {Event} event - Événement de clic
         */
        function closeProductModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('product-modal').classList.remove('active');
            document.body.style.overflow = '';
            currentProductImages = [];
            currentImageIndex = 0;
        }
        
        /**
         * Change l'image du carousel
         * @param {number} direction - Direction (-1 pour précédent, 1 pour suivant)
         */
        function changeCarouselImage(direction) {
            currentImageIndex += direction;
            
            // Boucler si nécessaire
            if (currentImageIndex < 0) {
                currentImageIndex = currentProductImages.length - 1;
            } else if (currentImageIndex >= currentProductImages.length) {
                currentImageIndex = 0;
            }
            
            updateCarousel();
            updateThumbnailSelection();
        }
        
        /**
         * Sélectionne une image spécifique dans le carousel
         * @param {number} index - Index de l'image
         */
        function selectCarouselImage(index) {
            currentImageIndex = index;
            updateCarousel();
            updateThumbnailSelection();
        }
        
        /**
         * Met à jour l'affichage du carousel
         */
        function updateCarousel() {
            const imgEl = document.getElementById('modal-carousel-image');
            const counterEl = document.getElementById('carousel-counter');
            
            imgEl.src = currentProductImages[currentImageIndex];
            counterEl.textContent = (currentImageIndex + 1) + ' / ' + currentProductImages.length;
            
            // Activer/désactiver les boutons de navigation
            const prevBtn = document.querySelector('.carousel-nav.prev');
            const nextBtn = document.querySelector('.carousel-nav.next');
            
            if (currentProductImages.length <= 1) {
                prevBtn.style.display = 'none';
                nextBtn.style.display = 'none';
                counterEl.style.display = 'none';
            } else {
                prevBtn.style.display = 'flex';
                nextBtn.style.display = 'flex';
                counterEl.style.display = 'block';
            }
        }
        
        /**
         * Génère les miniatures du carousel
         */
        function generateThumbnails() {
            const container = document.getElementById('carousel-thumbnails');
            container.innerHTML = '';
            
            currentProductImages.forEach((img, index) => {
                const thumb = document.createElement('div');
                thumb.className = 'carousel-thumb' + (index === 0 ? ' active' : '');
                thumb.onclick = () => selectCarouselImage(index);
                
                const thumbImg = document.createElement('img');
                thumbImg.src = img;
                thumbImg.alt = 'Image ' + (index + 1);
                
                thumb.appendChild(thumbImg);
                container.appendChild(thumb);
            });
        }
        
        /**
         * Met à jour la sélection des miniatures
         */
        function updateThumbnailSelection() {
            const thumbs = document.querySelectorAll('.carousel-thumb');
            thumbs.forEach((thumb, index) => {
                thumb.classList.toggle('active', index === currentImageIndex);
            });
        }
        
        // ============================================================================
        // GESTION DES COMMANDES
        // ============================================================================
        
        /**
         * Passe à la validation de la commande
         */
        function proceedToCheckout() {
            toggleCart();
            if (hasCustomerId) {
                document.getElementById('existing-order-modal').classList.add('active');
            } else {
                document.getElementById('order-form-modal').classList.add('active');
            }
            document.body.style.overflow = 'hidden';
        }
        
        /**
         * Ferme le formulaire de commande
         */
        function closeOrderForm() {
            document.getElementById('order-form-modal').classList.remove('active');
            const existingModal = document.getElementById('existing-order-modal');
            if (existingModal) existingModal.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // ============================================================================
        // UTILITAIRES
        // ============================================================================
        
        /**
         * Formate un prix
         * @param {number} price - Prix à formater
         * @returns {string} Prix formaté
         */
        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' <?php echo $data['settings']['currency']; ?>';
        }
        
        // ============================================================================
        // GESTION DES ÉVÉNEMENTS
        // ============================================================================
        
        // Swipe pour fermer les modals sur mobile
        let touchStartY = 0;
        
        document.querySelectorAll('.product-modal, .order-form-modal').forEach(modal => {
            modal.addEventListener('touchstart', function(e) {
                touchStartY = e.touches[0].clientY;
            }, { passive: true });
            
            modal.addEventListener('touchend', function(e) {
                const touchEndY = e.changedTouches[0].clientY;
                if (touchEndY - touchStartY > 100) {
                    closeProductModal();
                    closeOrderForm();
                }
            }, { passive: true });
        });
        
        // Fermer le formulaire de commande au clic sur l'overlay
        document.getElementById('order-form-modal').addEventListener('click', function(e) {
            if (e.target === this) closeOrderForm();
        });
        
        <?php if (isset($_SESSION['customer_id'])): ?>
        document.getElementById('existing-order-modal').addEventListener('click', function(e) {
            if (e.target === this) closeOrderForm();
        });
        <?php endif; ?>
        
        // Navigation au clavier pour le carousel
        document.addEventListener('keydown', function(e) {
            if (!document.getElementById('product-modal').classList.contains('active')) return;
            
            if (e.key === 'ArrowLeft') {
                changeCarouselImage(-1);
            } else if (e.key === 'ArrowRight') {
                changeCarouselImage(1);
            } else if (e.key === 'Escape') {
                closeProductModal();
            }
        });
        
        // ============================================================================
        // INITIALISATION
        // ============================================================================
        
        document.addEventListener('DOMContentLoaded', function() {
            // Animation d'entrée pour les produits
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>
