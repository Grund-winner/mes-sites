<?php
/**
 * POWERFIT GYM - Site Salle de Sport
 * 
 * Page d'accueil moderne avec:
 * - Présentation de la salle
 * - Services proposés
 * - Grille tarifaire
 * - Témoignages clients
 * - Bouton WhatsApp pour inscription
 * 
 * @version 1.0
 */

session_start();

define('DATA_FILE', 'data.json');

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
            'name' => 'PowerFit Gym',
            'tagline' => 'Transformez votre corps',
            'phone' => '22890000000',
            'address' => 'Lomé, Togo'
        ],
        'tarifs' => [],
        'services' => [],
        'testimonials' => [],
        'settings' => [
            'theme' => 'theme-11-neon',
            'whatsapp_message' => 'Bonjour, je souhaite m\'inscrire !'
        ]
    ];
}

$data = loadData();
$currentTheme = $data['settings']['theme'] ?? 'theme-11-neon';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['site']['name']); ?> - <?php echo htmlspecialchars($data['site']['tagline']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/<?php echo htmlspecialchars($currentTheme); ?>.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            overflow-x: hidden;
        }
        
        /* HEADER */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid #2a2a3e;
        }
        
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2rem;
            color: var(--primary);
            text-decoration: none;
            letter-spacing: 2px;
        }
        
        .nav {
            display: none;
            gap: 2rem;
        }
        
        .nav a {
            color: #ffffff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav a:hover {
            color: var(--primary);
        }
        
        .btn-cta {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #0a0a0a;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        
        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.5);
        }
        
        /* HERO */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
        }
        
        .hero-bg {
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=1920') center/cover;
            opacity: 0.3;
        }
        
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 212, 255, 0.1) 100%);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 6rem 1.5rem 2rem;
            text-align: center;
        }
        
        .hero-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--primary);
            border-radius: 50px;
            font-size: 0.85rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        
        .hero h1 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 4rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: 3px;
        }
        
        .hero h1 span {
            color: var(--primary);
        }
        
        .hero p {
            font-size: 1.1rem;
            color: #aaa;
            max-width: 600px;
            margin: 0 auto 2rem;
            line-height: 1.7;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-outline {
            padding: 0.875rem 2rem;
            background: transparent;
            color: #ffffff;
            text-decoration: none;
            border: 2px solid #ffffff;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-outline:hover {
            background: #ffffff;
            color: #0a0a0a;
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 4rem;
            flex-wrap: wrap;
        }
        
        .hero-stat {
            text-align: center;
        }
        
        .hero-stat-value {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            color: var(--primary);
        }
        
        .hero-stat-label {
            font-size: 0.85rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* SERVICES */
        .services {
            padding: 6rem 1.5rem;
            background: #0d1117;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-header h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }
        
        .section-header p {
            color: #888;
            font-size: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .service-card {
            background: #161b22;
            border: 1px solid #2a2a3e;
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s;
        }
        
        .service-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.1);
        }
        
        .service-icon {
            width: 60px;
            height: 60px;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        
        .service-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
        }
        
        .service-card p {
            color: #888;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        /* TARIFS */
        .tarifs {
            padding: 6rem 1.5rem;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
        }
        
        .tarifs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .tarif-card {
            background: #161b22;
            border: 1px solid #2a2a3e;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            transition: all 0.3s;
        }
        
        .tarif-card:hover {
            transform: translateY(-5px);
        }
        
        .tarif-card.popular {
            border-color: var(--primary);
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.2);
        }
        
        .tarif-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #0a0a0a;
            padding: 0.35rem 1.5rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .tarif-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .tarif-duration {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .tarif-price {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 4rem;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .tarif-price span {
            font-size: 1rem;
            color: #888;
            font-family: 'Inter', sans-serif;
        }
        
        .tarif-features {
            list-style: none;
            margin: 2rem 0;
            text-align: left;
        }
        
        .tarif-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #2a2a3e;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }
        
        .tarif-features li:last-child {
            border-bottom: none;
        }
        
        .tarif-features i {
            color: var(--primary);
            font-size: 0.8rem;
        }
        
        .btn-tarif {
            display: block;
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #0a0a0a;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }
        
        .btn-tarif:hover {
            box-shadow: 0 0 25px rgba(0, 255, 136, 0.4);
        }
        
        .tarif-card.popular .btn-tarif {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        
        /* TESTIMONIALS */
        .testimonials {
            padding: 6rem 1.5rem;
            background: #0d1117;
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .testimonial-card {
            background: #161b22;
            border: 1px solid #2a2a3e;
            border-radius: 16px;
            padding: 2rem;
        }
        
        .testimonial-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .testimonial-info h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        
        .testimonial-result {
            font-size: 0.8rem;
            color: var(--primary);
        }
        
        .testimonial-stars {
            color: #ffd700;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }
        
        .testimonial-text {
            color: #aaa;
            font-size: 0.95rem;
            line-height: 1.7;
            font-style: italic;
        }
        
        /* GALLERY */
        .gallery {
            padding: 6rem 1.5rem;
            background: #0a0a0a;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .gallery-item {
            aspect-ratio: 16/10;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .gallery-item:hover img {
            transform: scale(1.1);
        }
        
        /* CONTACT / CTA */
        .cta {
            padding: 6rem 1.5rem;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 212, 255, 0.1) 100%);
            text-align: center;
        }
        
        .cta h2 {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }
        
        .cta p {
            color: #aaa;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-whatsapp {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s;
            box-shadow: 0 0 25px rgba(37, 211, 102, 0.3);
        }
        
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 35px rgba(37, 211, 102, 0.5);
        }
        
        .btn-whatsapp i {
            font-size: 1.25rem;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #aaa;
        }
        
        .contact-item i {
            color: var(--primary);
        }
        
        /* FOOTER */
        .footer {
            background: #0d1117;
            border-top: 1px solid #2a2a3e;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer p {
            color: #666;
            font-size: 0.85rem;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .footer-links a {
            color: #888;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        /* WHATSAPP FLOATING BUTTON */
        .whatsapp-float {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #25d366, #128c7e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            text-decoration: none;
            box-shadow: 0 5px 25px rgba(37, 211, 102, 0.4);
            z-index: 999;
            transition: all 0.3s;
            animation: pulse-whatsapp 2s infinite;
        }
        
        .whatsapp-float:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 35px rgba(37, 211, 102, 0.5);
        }
        
        @keyframes pulse-whatsapp {
            0%, 100% { box-shadow: 0 5px 25px rgba(37, 211, 102, 0.4); }
            50% { box-shadow: 0 5px 35px rgba(37, 211, 102, 0.6); }
        }
        
        /* RESPONSIVE */
        @media (min-width: 768px) {
            .nav {
                display: flex;
            }
            
            .hero h1 {
                font-size: 5rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .section-header h2 {
                font-size: 2rem;
            }
            
            .hero-stats {
                gap: 1.5rem;
            }
            
            .hero-stat-value {
                font-size: 2rem;
            }
            
            .tarifs-grid {
                grid-template-columns: 1fr;
            }
            
            .cta h2 {
                font-size: 2rem;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="header-container">
            <a href="#" class="logo"><?php echo htmlspecialchars($data['site']['name']); ?></a>
            
            <nav class="nav">
                <a href="#services">Services</a>
                <a href="#tarifs">Tarifs</a>
                <a href="#temoignages">Témoignages</a>
                <a href="#contact">Contact</a>
            </nav>
            
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $data['site']['phone']); ?>?text=<?php echo urlencode($data['settings']['whatsapp_message'] ?? 'Bonjour, je souhaite m\'inscrire !'); ?>" class="btn-cta" target="_blank">
                S'inscrire
            </a>
        </div>
    </header>
    
    <!-- HERO -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-fire"></i> La salle #1 à Lomé
            </div>
            <h1>TRANSFORMEZ<br>VOTRE <span>CORPS</span></h1>
            <p><?php echo htmlspecialchars($data['site']['description']); ?></p>
            <div class="hero-buttons">
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $data['site']['phone']); ?>?text=<?php echo urlencode('Bonjour, je souhaite avoir des infos sur les abonnements.'); ?>" class="btn-cta" target="_blank">
                    <i class="fab fa-whatsapp"></i> S'inscrire maintenant
                </a>
                <a href="#tarifs" class="btn-outline">Voir les tarifs</a>
            </div>
            
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="hero-stat-value"><?php echo $data['stats']['members'] ?? '500+'; ?></div>
                    <div class="hero-stat-label">Membres actifs</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value"><?php echo $data['stats']['coaches'] ?? '8'; ?></div>
                    <div class="hero-stat-label">Coachs diplômés</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value"><?php echo $data['stats']['equipment'] ?? '100+'; ?></div>
                    <div class="hero-stat-label">Équipements</div>
                </div>
                <div class="hero-stat">
                    <div class="hero-stat-value"><?php echo $data['stats']['years'] ?? '5'; ?></div>
                    <div class="hero-stat-label">Années d'expérience</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- SERVICES -->
    <section class="services" id="services">
        <div class="section-header">
            <h2>NOS SERVICES</h2>
            <p>Tout ce dont vous avez besoin pour atteindre vos objectifs fitness</p>
        </div>
        
        <div class="services-grid">
            <?php foreach ($data['services'] ?? [] as $service): ?>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas <?php echo htmlspecialchars($service['icon'] ?? 'fa-dumbbell'); ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                <p><?php echo htmlspecialchars($service['description']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- TARIFS -->
    <section class="tarifs" id="tarifs">
        <div class="section-header">
            <h2>NOS TARIFS</h2>
            <p>Choisissez l'abonnement qui vous convient</p>
        </div>
        
        <div class="tarifs-grid">
            <?php foreach ($data['tarifs'] ?? [] as $tarif): ?>
            <div class="tarif-card <?php echo ($tarif['popular'] ?? false) ? 'popular' : ''; ?>">
                <?php if ($tarif['popular'] ?? false): ?>
                <div class="tarif-badge">Le plus populaire</div>
                <?php endif; ?>
                
                <div class="tarif-name"><?php echo htmlspecialchars($tarif['name']); ?></div>
                <div class="tarif-duration"><?php echo htmlspecialchars($tarif['duration']); ?></div>
                <div class="tarif-price">
                    <?php echo number_format($tarif['price'], 0, ',', ' '); ?>
                    <span>FCFA</span>
                </div>
                
                <ul class="tarif-features">
                    <?php foreach ($tarif['features'] ?? [] as $feature): ?>
                    <li>
                        <i class="fas fa-check"></i>
                        <?php echo htmlspecialchars($feature); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $data['site']['phone']); ?>?text=<?php echo urlencode('Bonjour, je souhaite m\'inscrire à l\'abonnement ' . $tarif['name'] . ' (' . $tarif['duration'] . ')'); ?>" class="btn-tarif" target="_blank">
                    <i class="fab fa-whatsapp"></i> Choisir ce plan
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- TESTIMONIALS -->
    <section class="testimonials" id="temoignages">
        <div class="section-header">
            <h2>ILS TÉMOIGNENT</h2>
            <p>Ce que nos membres disent de nous</p>
        </div>
        
        <div class="testimonials-grid">
            <?php foreach ($data['testimonials'] ?? [] as $testimonial): ?>
            <div class="testimonial-card">
                <div class="testimonial-header">
                    <img src="<?php echo htmlspecialchars($testimonial['avatar']); ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" class="testimonial-avatar">
                    <div class="testimonial-info">
                        <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                        <div class="testimonial-result"><?php echo htmlspecialchars($testimonial['result'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="testimonial-stars">
                    <?php for ($i = 0; $i < ($testimonial['rating'] ?? 5); $i++): ?>
                    <i class="fas fa-star"></i>
                    <?php endfor; ?>
                </div>
                <p class="testimonial-text">"<?php echo htmlspecialchars($testimonial['text']); ?>"</p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- GALLERY -->
    <section class="gallery">
        <div class="section-header">
            <h2>NOTRE SALLE</h2>
            <p>Découvrez nos installations</p>
        </div>
        
        <div class="gallery-grid">
            <?php foreach ($data['gallery'] ?? [] as $img): ?>
            <div class="gallery-item">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="Galerie">
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- CTA -->
    <section class="cta" id="contact">
        <h2>PRÊT À COMMENCER ?</h2>
        <p>Rejoignez la communauté <?php echo htmlspecialchars($data['site']['name']); ?> et commencez votre transformation dès aujourd'hui !</p>
        
        <div class="cta-buttons">
            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $data['site']['phone']); ?>?text=<?php echo urlencode($data['settings']['whatsapp_message'] ?? 'Bonjour, je souhaite m\'inscrire !'); ?>" class="btn-whatsapp" target="_blank">
                <i class="fab fa-whatsapp"></i>
                Nous contacter sur WhatsApp
            </a>
        </div>
        
        <div class="contact-info">
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo htmlspecialchars($data['site']['address']); ?></span>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <span><?php echo htmlspecialchars($data['site']['phone']); ?></span>
            </div>
            <div class="contact-item">
                <i class="fas fa-clock"></i>
                <span>Lun-Ven: <?php echo $data['site']['hours']['weekdays'] ?? '5h-22h'; ?> | Sam-Dim: <?php echo $data['site']['hours']['weekend'] ?? '7h-20h'; ?></span>
            </div>
        </div>
    </section>
    
    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($data['site']['name']); ?>. Tous droits réservés.</p>
            <div class="footer-links">
                <a href="admin.php">Administration</a>
            </div>
        </div>
    </footer>
    
    <!-- WHATSAPP FLOATING BUTTON -->
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $data['site']['phone']); ?>?text=<?php echo urlencode($data['settings']['whatsapp_message'] ?? 'Bonjour !'); ?>" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>
</body>
</html>
