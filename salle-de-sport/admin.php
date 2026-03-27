<?php
/**
 * POWERFIT GYM - Administration
 * 
 * Gestion de la salle de sport:
 * - Tarifs et abonnements
 * - Services
 * - Témoignages clients
 * - Galerie photos
 * - Paramètres
 * 
 * @version 1.0
 */

session_start();

define('DATA_FILE', 'data.json');
define('UPLOADS_DIR', 'uploads/');
define('GALLERY_DIR', UPLOADS_DIR . 'gallery/');

// Créer les dossiers si nécessaires
if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0755, true);
if (!is_dir(GALLERY_DIR)) mkdir(GALLERY_DIR, 0755, true);

$availableThemes = [
    'theme-11-neon' => ['name' => 'Néon Futuriste', 'color' => '#00ff88', 'icon' => '⚡'],
    'theme-12-minimal' => ['name' => 'Minimaliste', 'color' => '#64748b', 'icon' => '◽'],
    'theme-5-rouge' => ['name' => 'Rouge Passion', 'color' => '#dc2626', 'icon' => '❤️'],
    'theme-7-orange' => ['name' => 'Orange Énergie', 'color' => '#f97316', 'icon' => '☀️'],
];

function loadData() {
    if (file_exists(DATA_FILE)) {
        $json = file_get_contents(DATA_FILE);
        $data = json_decode($json, true);
        if ($data !== null) return $data;
    }
    return getDefaultData();
}

function getDefaultData() {
    return [
        'site' => ['name' => 'PowerFit Gym', 'phone' => '22890000000', 'address' => 'Lomé, Togo'],
        'tarifs' => [],
        'services' => [],
        'testimonials' => [],
        'gallery' => [],
        'admin' => ['username' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT)],
        'settings' => ['theme' => 'theme-11-neon', 'whatsapp_message' => 'Bonjour !']
    ];
}

function saveData($data) {
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

$data = loadData();

// Initialiser les données si nécessaires
if (!isset($data['admin'])) {
    $data['admin'] = ['username' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT)];
    saveData($data);
}

// Vérification connexion
function isLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) return false;
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity'] > 3600)) {
        session_destroy();
        return false;
    }
    $_SESSION['admin_last_activity'] = time();
    return true;
}

$showLogin = !isLoggedIn() && !isset($_POST['login']);
$error = '';

// Login
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

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$message = '';
$messageType = '';
$page = $_GET['page'] ?? 'dashboard';

// Changement mot de passe
if (isset($_POST['change_password'])) {
    if (!password_verify($_POST['current_password'], $data['admin']['password'])) {
        $message = 'Mot de passe actuel incorrect';
        $messageType = 'error';
    } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
        $message = 'Les mots de passe ne correspondent pas';
        $messageType = 'error';
    } elseif (strlen($_POST['new_password']) < 6) {
        $message = 'Minimum 6 caractères';
        $messageType = 'error';
    } else {
        $data['admin']['password'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        saveData($data);
        $message = 'Mot de passe modifié !';
        $messageType = 'success';
    }
}

// GESTION DES TARIFS
if (isset($_POST['add_tarif'])) {
    $data['tarifs'][] = [
        'id' => time(),
        'name' => $_POST['tarif_name'],
        'price' => intval($_POST['tarif_price']),
        'duration' => $_POST['tarif_duration'],
        'features' => array_filter(explode("\n", $_POST['tarif_features'])),
        'popular' => isset($_POST['tarif_popular'])
    ];
    saveData($data);
    $message = 'Tarif ajouté';
    $messageType = 'success';
}

if (isset($_POST['edit_tarif'])) {
    $id = intval($_POST['tarif_id']);
    foreach ($data['tarifs'] as &$t) {
        if ($t['id'] === $id) {
            $t['name'] = $_POST['tarif_name'];
            $t['price'] = intval($_POST['tarif_price']);
            $t['duration'] = $_POST['tarif_duration'];
            $t['features'] = array_filter(explode("\n", $_POST['tarif_features']));
            $t['popular'] = isset($_POST['tarif_popular']);
            break;
        }
    }
    saveData($data);
    $message = 'Tarif modifié';
    $messageType = 'success';
}

if (isset($_GET['delete_tarif'])) {
    $data['tarifs'] = array_filter($data['tarifs'], fn($t) => $t['id'] !== intval($_GET['delete_tarif']));
    $data['tarifs'] = array_values($data['tarifs']);
    saveData($data);
    $message = 'Tarif supprimé';
    $messageType = 'success';
}

// GESTION DES SERVICES
if (isset($_POST['add_service'])) {
    $data['services'][] = [
        'id' => time(),
        'name' => $_POST['service_name'],
        'icon' => $_POST['service_icon'],
        'description' => $_POST['service_description']
    ];
    saveData($data);
    $message = 'Service ajouté';
    $messageType = 'success';
}

if (isset($_GET['delete_service'])) {
    $data['services'] = array_filter($data['services'], fn($s) => $s['id'] !== intval($_GET['delete_service']));
    $data['services'] = array_values($data['services']);
    saveData($data);
    $message = 'Service supprimé';
    $messageType = 'success';
}

// GESTION DES TÉMOIGNAGES
if (isset($_POST['add_testimonial'])) {
    $data['testimonials'][] = [
        'id' => time(),
        'name' => $_POST['test_name'],
        'avatar' => $_POST['test_avatar'],
        'text' => $_POST['test_text'],
        'rating' => intval($_POST['test_rating']),
        'result' => $_POST['test_result']
    ];
    saveData($data);
    $message = 'Témoignage ajouté';
    $messageType = 'success';
}

if (isset($_GET['delete_testimonial'])) {
    $data['testimonials'] = array_filter($data['testimonials'], fn($t) => $t['id'] !== intval($_GET['delete_testimonial']));
    $data['testimonials'] = array_values($data['testimonials']);
    saveData($data);
    $message = 'Témoignage supprimé';
    $messageType = 'success';
}

// PARAMÈTRES DU SITE
if (isset($_POST['save_settings'])) {
    $data['site']['name'] = $_POST['site_name'];
    $data['site']['tagline'] = $_POST['site_tagline'];
    $data['site']['description'] = $_POST['site_description'];
    $data['site']['phone'] = $_POST['site_phone'];
    $data['site']['email'] = $_POST['site_email'];
    $data['site']['address'] = $_POST['site_address'];
    $data['site']['hours']['weekdays'] = $_POST['hours_weekdays'];
    $data['site']['hours']['weekend'] = $_POST['hours_weekend'];
    $data['settings']['whatsapp_message'] = $_POST['whatsapp_message'];
    saveData($data);
    $message = 'Paramètres sauvegardés';
    $messageType = 'success';
}

if (isset($_POST['change_theme'])) {
    $data['settings']['theme'] = $_POST['theme'];
    saveData($data);
    $message = 'Thème changé';
    $messageType = 'success';
}

// Stats
$totalTarifs = count($data['tarifs'] ?? []);
$totalServices = count($data['services'] ?? []);
$totalTestimonials = count($data['testimonials'] ?? []);

$currentTheme = $data['settings']['theme'] ?? 'theme-11-neon';

// Édition
$editingTarif = null;
if (isset($_GET['edit_tarif'])) {
    $id = intval($_GET['edit_tarif']);
    foreach ($data['tarifs'] ?? [] as $t) {
        if ($t['id'] === $id) {
            $editingTarif = $t;
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
    <title>Admin - <?php echo htmlspecialchars($data['site']['name'] ?? 'Gym'); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($currentTheme); ?>.css">
    
    <style>
        :root { --sidebar-width: 240px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0d1117;
            color: #fff;
            min-height: 100vh;
        }
        
        /* Login */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a0a0a, #1a1a2e);
            padding: 1rem;
        }
        
        .login-box {
            background: #161b22;
            border: 1px solid #2a2a3e;
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 380px;
        }
        
        .login-box h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .login-box p {
            color: #888;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: #0d1117;
            border: 1px solid #2a2a3e;
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        
        .btn-primary { background: var(--primary); color: #0a0a0a; }
        .btn-secondary { background: #2a2a3e; color: #fff; }
        .btn-danger { background: #ff4466; color: #fff; }
        
        .btn-block { width: 100%; justify-content: center; }
        
        .error-message {
            background: rgba(255, 68, 102, 0.1);
            border: 1px solid #ff4466;
            color: #ff4466;
            padding: 0.875rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
        }
        
        /* Layout */
        .admin-container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: var(--sidebar-width);
            background: #161b22;
            border-right: 1px solid #2a2a3e;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 1.25rem;
            border-bottom: 1px solid #2a2a3e;
        }
        
        .sidebar-header h2 {
            font-size: 1.1rem;
            color: var(--primary);
        }
        
        .sidebar-nav {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: #888;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(0, 255, 136, 0.1);
            color: var(--primary);
        }
        
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid #2a2a3e;
        }
        
        .btn-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: #2a2a3e;
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.85rem;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .page-header h1 { font-size: 1.5rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        
        .btn-logout {
            padding: 0.5rem 1rem;
            background: transparent;
            border: 1px solid #2a2a3e;
            color: #888;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.8rem;
        }
        
        .btn-logout:hover {
            border-color: #ff4466;
            color: #ff4466;
        }
        
        /* Cards */
        .card {
            background: #161b22;
            border: 1px solid #2a2a3e;
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            padding: 1.25rem;
            border-bottom: 1px solid #2a2a3e;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 { font-size: 1rem; }
        
        .card-body { padding: 1.25rem; }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: #161b22;
            border: 1px solid #2a2a3e;
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
        }
        
        .stat-card-icon {
            width: 50px;
            height: 50px;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--primary);
            margin: 0 auto 1rem;
        }
        
        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-card-label { font-size: 0.85rem; color: #888; }
        
        /* Tables */
        .table-container { overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; }
        
        th, td {
            padding: 0.875rem;
            text-align: left;
            border-bottom: 1px solid #2a2a3e;
            font-size: 0.85rem;
        }
        
        th { font-weight: 600; color: #888; text-transform: uppercase; font-size: 0.7rem; }
        
        tr:hover td { background: rgba(0, 255, 136, 0.03); }
        
        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }
        
        .alert-success { background: rgba(0, 255, 136, 0.1); border: 1px solid var(--primary); color: var(--primary); }
        .alert-error { background: rgba(255, 68, 102, 0.1); border: 1px solid #ff4466; color: #ff4466; }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .form-group.full-width { grid-column: span 2; }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        
        /* Actions */
        .btn-action {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #2a2a3e;
            color: #888;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-action:hover { background: var(--primary); color: #0a0a0a; }
        .btn-action.delete:hover { background: #ff4466; }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .badge-success { background: rgba(0, 255, 136, 0.1); color: var(--primary); }
        .badge-warning { background: rgba(255, 204, 0, 0.1); color: #ffcc00; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <?php if ($showLogin): ?>
    <div class="login-container">
        <div class="login-box">
            <h1><?php echo htmlspecialchars($data['site']['name'] ?? 'Gym Admin'); ?></h1>
            <p>Administration</p>
            
            <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-block">Se connecter</button>
            </form>
            <p style="margin-top: 1.5rem; text-align: center; font-size: 0.8rem; color: #666;">Défaut: admin / admin123</p>
        </div>
    </div>
    <?php else: ?>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($data['site']['name'] ?? 'Gym'); ?></h2>
            </div>
            <nav class="sidebar-nav">
                <a href="?page=dashboard" class="nav-item <?php echo $page === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Tableau de bord</a>
                <a href="?page=tarifs" class="nav-item <?php echo $page === 'tarifs' ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Tarifs</a>
                <a href="?page=services" class="nav-item <?php echo $page === 'services' ? 'active' : ''; ?>"><i class="fas fa-dumbbell"></i> Services</a>
                <a href="?page=testimonials" class="nav-item <?php echo $page === 'testimonials' ? 'active' : ''; ?>"><i class="fas fa-comments"></i> Témoignages</a>
                <a href="?page=settings" class="nav-item <?php echo $page === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Paramètres</a>
                <a href="?page=security" class="nav-item <?php echo $page === 'security' ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i> Sécurité</a>
            </nav>
            <div class="sidebar-footer">
                <a href="index.php" class="btn-back" target="_blank"><i class="fas fa-external-link-alt"></i> Voir le site</a>
            </div>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1><?php
                    $titles = [
                        'dashboard' => 'Tableau de bord',
                        'tarifs' => 'Gestion des tarifs',
                        'services' => 'Gestion des services',
                        'testimonials' => 'Témoignages clients',
                        'settings' => 'Paramètres',
                        'security' => 'Sécurité'
                    ];
                    echo $titles[$page] ?? 'Administration';
                ?></h1>
                <div class="user-menu">
                    <a href="?logout" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
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
                    <div class="stat-card-icon"><i class="fas fa-tags"></i></div>
                    <div class="stat-card-value"><?php echo $totalTarifs; ?></div>
                    <div class="stat-card-label">Tarifs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-dumbbell"></i></div>
                    <div class="stat-card-value"><?php echo $totalServices; ?></div>
                    <div class="stat-card-label">Services</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-comments"></i></div>
                    <div class="stat-card-value"><?php echo $totalTestimonials; ?></div>
                    <div class="stat-card-label">Témoignages</div>
                </div>
            </div>
            
            <?php elseif ($page === 'tarifs'): ?>
            <!-- TARIFS -->
            <div class="card">
                <div class="card-header">
                    <h2><?php echo $editingTarif ? 'Modifier le tarif' : 'Ajouter un tarif'; ?></h2>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?php if ($editingTarif): ?>
                        <input type="hidden" name="tarif_id" value="<?php echo $editingTarif['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" name="tarif_name" required value="<?php echo htmlspecialchars($editingTarif['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Prix (FCFA) *</label>
                                <input type="number" name="tarif_price" required value="<?php echo $editingTarif['price'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Durée *</label>
                                <input type="text" name="tarif_duration" required value="<?php echo htmlspecialchars($editingTarif['duration'] ?? ''); ?>" placeholder="Ex: 3 mois">
                            </div>
                            <div class="form-group">
                                <label>Populaire</label>
                                <select name="tarif_popular">
                                    <option value="0" <?php echo !($editingTarif['popular'] ?? false) ? 'selected' : ''; ?>>Non</option>
                                    <option value="1" <?php echo ($editingTarif['popular'] ?? false) ? 'selected' : ''; ?>>Oui</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label>Avantages (un par ligne)</label>
                                <textarea name="tarif_features" rows="4"><?php echo htmlspecialchars(implode("\n", $editingTarif['features'] ?? [])); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($editingTarif): ?>
                            <a href="?page=tarifs" class="btn btn-secondary">Annuler</a>
                            <button type="submit" name="edit_tarif" class="btn btn-primary">Modifier</button>
                            <?php else: ?>
                            <button type="submit" name="add_tarif" class="btn btn-primary">Ajouter</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><h2>Liste des tarifs</h2></div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Nom</th><th>Prix</th><th>Durée</th><th>Populaire</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($data['tarifs'] ?? [] as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['name']); ?></td>
                                <td><?php echo number_format($t['price'], 0, ',', ' '); ?> FCFA</td>
                                <td><?php echo htmlspecialchars($t['duration']); ?></td>
                                <td><?php echo ($t['popular'] ?? false) ? '<span class="badge badge-success">Oui</span>' : '<span class="badge badge-warning">Non</span>'; ?></td>
                                <td>
                                    <a href="?page=tarifs&edit_tarif=<?php echo $t['id']; ?>" class="btn-action"><i class="fas fa-edit"></i></a>
                                    <a href="?page=tarifs&delete_tarif=<?php echo $t['id']; ?>" class="btn-action delete" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php elseif ($page === 'services'): ?>
            <!-- SERVICES -->
            <div class="card">
                <div class="card-header"><h2>Ajouter un service</h2></div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" name="service_name" required>
                            </div>
                            <div class="form-group">
                                <label>Icône Font Awesome</label>
                                <input type="text" name="service_icon" placeholder="fa-dumbbell">
                            </div>
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="service_description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add_service" class="btn btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><h2>Liste des services</h2></div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Nom</th><th>Icône</th><th>Description</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($data['services'] ?? [] as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><i class="fas <?php echo htmlspecialchars($s['icon'] ?? 'fa-dumbbell'); ?>"></i></td>
                                <td><?php echo htmlspecialchars($s['description']); ?></td>
                                <td>
                                    <a href="?page=services&delete_service=<?php echo $s['id']; ?>" class="btn-action delete" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php elseif ($page === 'testimonials'): ?>
            <!-- TÉMOIGNAGES -->
            <div class="card">
                <div class="card-header"><h2>Ajouter un témoignage</h2></div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nom *</label>
                                <input type="text" name="test_name" required>
                            </div>
                            <div class="form-group">
                                <label>URL Avatar</label>
                                <input type="text" name="test_avatar" placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label>Note (1-5)</label>
                                <select name="test_rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i === 5 ? 'selected' : ''; ?>><?php echo $i; ?> étoile(s)</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Résultat</label>
                                <input type="text" name="test_result" placeholder="Ex: -15kg en 6 mois">
                            </div>
                            <div class="form-group full-width">
                                <label>Témoignage *</label>
                                <textarea name="test_text" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add_testimonial" class="btn btn-primary">Ajouter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><h2>Liste des témoignages</h2></div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Nom</th><th>Note</th><th>Résultat</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($data['testimonials'] ?? [] as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['name']); ?></td>
                                <td><?php echo str_repeat('⭐', $t['rating'] ?? 5); ?></td>
                                <td><?php echo htmlspecialchars($t['result'] ?? ''); ?></td>
                                <td>
                                    <a href="?page=testimonials&delete_testimonial=<?php echo $t['id']; ?>" class="btn-action delete" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php elseif ($page === 'settings'): ?>
            <!-- PARAMÈTRES -->
            <div class="card">
                <div class="card-header"><h2>Informations du site</h2></div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nom</label>
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
                            <div class="form-group">
                                <label>Horaires semaine</label>
                                <input type="text" name="hours_weekdays" value="<?php echo htmlspecialchars($data['site']['hours']['weekdays'] ?? '5h00 - 22h00'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Horaires weekend</label>
                                <input type="text" name="hours_weekend" value="<?php echo htmlspecialchars($data['site']['hours']['weekend'] ?? '7h00 - 20h00'); ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Description</label>
                                <textarea name="site_description" rows="3"><?php echo htmlspecialchars($data['site']['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label>Message WhatsApp par défaut</label>
                                <input type="text" name="whatsapp_message" value="<?php echo htmlspecialchars($data['settings']['whatsapp_message'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="save_settings" class="btn btn-primary">Sauvegarder</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header"><h2>Thème</h2></div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Choisir un thème</label>
                                <select name="theme">
                                    <?php foreach ($availableThemes as $id => $theme): ?>
                                    <option value="<?php echo $id; ?>" <?php echo ($currentTheme === $id) ? 'selected' : ''; ?>><?php echo $theme['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="change_theme" class="btn btn-primary">Appliquer</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php elseif ($page === 'security'): ?>
            <!-- SÉCURITÉ -->
            <div class="card">
                <div class="card-header"><h2>Changer le mot de passe</h2></div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Mot de passe actuel</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>Nouveau mot de passe</label>
                                <input type="password" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Confirmer</label>
                                <input type="password" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">Changer</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <?php endif; ?>
</body>
</html>
