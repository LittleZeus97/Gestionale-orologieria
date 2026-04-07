<?php
session_start();
require_once 'db.php';

$stmt = $pdo->query('
  SELECT m.CodMoneta, m.nome, m.prezzo, m.Materiale, m.AnnoEmi, u.nome AS autore
  FROM monete m
  JOIN utenti u ON m.UtentePubbli = u.CodUtente
');
$monete = $stmt->fetchAll();

$categories = [
    'Euro-Italia' => ['min' => 2002, 'max' => 9999], // Euro introdotto nel 2002
    'Repubblica-Italiana' => ['min' => 1946, 'max' => 2001],
    'Regno-d\'Italia-(1861-1922)' => ['min' => 1861, 'max' => 1922],
    'Regno-d\'Italia-(1922-1943)' => ['min' => 1922, 'max' => 1943],
    'Regnod\'Italia(1943-1946)' => ['min' => 1943, 'max' => 1946], // Corretto l'apostrofo
];

// Ottieni la categoria dalla query string
$cat = $_GET['cat'] ?? null;
$filter = null;
if ($cat && isset($categories[$cat])) {
    $filter = $categories[$cat];
}

// Query per le monete filtrate
$query = '
  SELECT m.CodMoneta, m.nome, m.prezzo, m.Materiale, m.AnnoEmi, u.nome AS autore
  FROM monete m
  JOIN utenti u ON m.UtentePubbli = u.CodUtente
';
$params = [];
if ($filter) {
    $query .= ' WHERE m.AnnoEmi BETWEEN ? AND ?';
    $params = [$filter['min'], $filter['max']];
}
$query .= ' ORDER BY m.AnnoEmi DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$monete = $stmt->fetchAll();


// Mappa dei tipi di materiale
$materiali = [
    'argento' => 'Argento',
    'oro' => 'Oro',
    'nickel' => 'Altro', // Per "nickel" filtra materiali diversi da argento e oro
];

// Gestione aggiunta monete (solo per utenti loggati)
$addError = '';
$addSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coin'])) {
  if (!isset($_SESSION['user_id'])) {
    $addError = 'Devi essere loggato per aggiungere monete.';
  } else {
    $nome = trim($_POST['nome'] ?? '');
    $prezzo = $_POST['prezzo'] ?? '';
    $materiale = $_POST['materiale'] ?? '';
    $annoEmi = $_POST['anno_emi'] ?? '';

    if ($nome === '' || $prezzo === '' || $materiale === '' || $annoEmi === '') {
      $addError = 'Compila tutti i campi.';
    } elseif (!is_numeric($prezzo) || $prezzo < 0) {
      $addError = 'Il prezzo deve essere un numero positivo.';
    } elseif (!is_numeric($annoEmi) || $annoEmi < 1000 || $annoEmi > 2100) {
      $addError = 'Anno di emissione non valido.';
    } else {
      try {
        $stmt = $pdo->prepare('INSERT INTO monete (nome, prezzo, Materiale, AnnoEmi, UtentePubbli) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$nome, $prezzo, $materiale, $annoEmi, $_SESSION['user_id']]);
        $addSuccess = 'Moneta aggiunta con successo!';
        header('Location: index.php');
        exit;
      } catch (Exception $e) {
        $addError = 'Errore nell\'aggiunta della moneta.';
      }
    }
  }
}

// Ottieni il tipo dalla query string
$tipo = $_GET['tipo'] ?? null;
$filter = null;
if ($tipo && isset($materiali[$tipo])) {
    $filter = $tipo;
}

// Query per le monete filtrate
$query = '
  SELECT m.CodMoneta, m.nome, m.prezzo, m.Materiale, m.AnnoEmi, u.nome AS autore
  FROM monete m
  JOIN utenti u ON m.UtentePubbli = u.CodUtente
';
$params = [];
if ($filter) {
    if ($filter === 'nickel') {
        $query .= ' WHERE m.Materiale NOT IN (?, ?)';
        $params = ['Argento', 'Oro'];
    } else {
        $query .= ' WHERE m.Materiale = ?';
        $params = [$materiali[$filter]];
    }
}
$query .= ' ORDER BY m.AnnoEmi DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$monete = $stmt->fetchAll();
?>



<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CoinColl</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Josefin+Sans:wght@200;300;400&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --cream:   #F5F2ED;
      --linen:   #EDE8DF;
      --taupe:   #C8BFB0;
      --warm-gray: #9A9186;
      --charcoal: #3A3530;
      --deep:    #1E1A17;
      --gold:    #B89A6A;
      --gold-lt: #D4B98A;
      --white:   #FDFBF8;
      --shadow:  rgba(30,26,23,0.12);
    }

    body {
      background: var(--cream);
      font-family: 'Josefin Sans', sans-serif;
      color: var(--charcoal);
    }

    /* â”€â”€â”€ TOP BAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .top-bar {
      background: var(--deep);
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding: 0 48px;
    }

    
    .top-bar a,
  .top-bar button {
    font-family: 'Josefin Sans', sans-serif;
    font-size: 11px;
    font-weight: 300;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    text-decoration: none;
    padding: 5px 18px;
    border-radius: 1px;
    transition: all 0.25s ease;
    cursor: pointer;
    background: none;
    border: none;
  }

  .top-bar form {
    display: inline-flex;
  }

    /* LOGIN â€“ ghost */
    .btn-login {
      color: var(--taupe);
      border: 1px solid rgba(200,191,176,0.35);
    }
    .btn-login:hover {
      color: var(--gold-lt);
      border-color: var(--gold);
      background: rgba(184,154,106,0.08);
    }

    /* SIGN UP â€“ filled */
    .btn-signup {
      color: var(--taupe);
      background: var(--gold);
      border: 1px solid var(--gold);
    }
    .btn-signup:hover {
      background: var(--gold-lt);
      border-color: var(--gold-lt);
    }

    .user-welcome {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--taupe);
      margin-right: 20px;
    }

    .add-coin-section {
      background: var(--white);
      border: 1px solid var(--linen);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
      padding: 28px;
      border-radius: 10px;
      margin-bottom: 30px;
    }

    .add-coin-title {
      margin-bottom: 22px;
      letter-spacing: 0.1em;
      font-size: 18px;
      font-weight: 600;
      color: var(--deep);
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      margin-bottom: 6px;
      color: var(--taupe);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #d4d0c6;
      border-radius: 6px;
      font-family: 'Josefin Sans', sans-serif;
      font-size: 14px;
      color: var(--deep);
      background: var(--cream);
    }

    .add-coin-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 12px 24px;
      background: var(--deep);
      color: var(--white);
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.2s ease;
    }

    .add-coin-btn:hover {
      background: #35322f;
    }

    .message {
      padding: 14px 16px;
      border-radius: 8px;
      margin-bottom: 18px;
      font-size: 13px;
      line-height: 1.4;
    }

    .message.error {
      background: #fdf0f0;
      color: #9a2b2b;
      border: 1px solid #f0c5c5;
    }

    .message.success {
      background: #f4faf3;
      color: #2e6d34;
      border: 1px solid #c7e4d0;
    }

    .user-welcome {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--taupe);
      margin-right: 20px;
    }

  

    /* â”€â”€â”€ MAIN HEADER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    header.main-header {
      background: var(--white);
      border-bottom: 1px solid var(--linen);
      box-shadow: 0 2px 24px var(--shadow);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .header-inner {
      max-width: 1320px;
      margin: 0 auto;
      padding: 0 48px;
      height: 80px;
      display: flex;
      align-items: center;
      gap: 48px;
    }

    /* â”€â”€â”€ LOGO â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€  */
    .logo {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      text-decoration: none;
      flex-shrink: 0;
      line-height: 1;
      gap: 2px;
    }
    .logo-word {
      font-family: 'Cormorant Garamond', serif;
      font-size: 28px;
      font-weight: 300;
      letter-spacing: 0.22em;
      color: var(--deep);
      text-transform: uppercase;
    }
    .logo-sub {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 9px;
      font-weight: 200;
      letter-spacing: 0.35em;
      color: var(--gold);
      text-transform: uppercase;
    }

    /* â”€â”€â”€ DIVIDER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€  */
    .divider {
      width: 1px;
      height: 36px;
      background: var(--linen);
      flex-shrink: 0;
    }

    /* â”€â”€â”€ NAV â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€  */
    nav.main-nav {
      flex: 1;
    }

    nav.main-nav > ul {
      display: flex;
      list-style: none;
      gap: 4px;
      align-items: center;
      height: 80px;
    }

    nav.main-nav > ul > li {
      position: relative;
      height: 100%;
      display: flex;
      align-items: center;
    }

    /* top-level link */
    nav.main-nav > ul > li > a {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--charcoal);
      text-decoration: none;
      padding: 0 16px;
      height: 100%;
      display: flex;
      align-items: center;
      position: relative;
      transition: color 0.2s ease;
    }

    nav.main-nav > ul > li > a::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 16px; right: 16px;
      height: 2px;
      background: var(--gold);
      transform: scaleX(0);
      transition: transform 0.25s ease;
      transform-origin: left;
    }

    nav.main-nav > ul > li:hover > a,
    nav.main-nav > ul > li > a.active {
      color: var(--deep);
    }
    nav.main-nav > ul > li:hover > a::after,
    nav.main-nav > ul > li > a.active::after {
      transform: scaleX(1);
    }

    /* HOME has no dropdown arrow */
    li.has-dropdown > a::before {
      content: 'â€º';
      position: absolute;
      bottom: 28px;
      left: 50%;
      transform: translateX(-50%) rotate(90deg);
      font-size: 10px;
      color: var(--gold);
      opacity: 0;
      transition: opacity 0.2s, bottom 0.2s;
    }
    li.has-dropdown:hover > a::before {
      opacity: 1;
      bottom: 24px;
    }

    /* â”€â”€â”€ DROPDOWN â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€  */
    .dropdown {
      position: absolute;
      top: calc(100% + 0px);
      left: 0;
      min-width: 220px;
      background: var(--white);
      border-top: 2px solid var(--gold);
      border-bottom: 1px solid var(--linen);
      border-left: 1px solid var(--linen);
      border-right: 1px solid var(--linen);
      box-shadow: 0 12px 40px var(--shadow);
      opacity: 0;
      visibility: hidden;
      transform: translateY(8px);
      transition: opacity 0.22s ease, transform 0.22s ease, visibility 0.22s;
      z-index: 200;
    }

    li.has-dropdown:hover .dropdown {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown ul {
      list-style: none;
      padding: 10px 0;
    }

    .dropdown ul li a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 22px;
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px;
      font-weight: 300;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--warm-gray);
      text-decoration: none;
      transition: color 0.18s, background 0.18s, padding-left 0.18s;
    }

    .dropdown ul li a:hover {
      color: var(--deep);
      background: var(--linen);
      padding-left: 28px;
    }

    .dropdown ul li a .dot {
      width: 4px; height: 4px;
      border-radius: 50%;
      background: var(--gold);
      flex-shrink: 0;
      opacity: 0;
      transition: opacity 0.18s;
    }
    .dropdown ul li a:hover .dot {
      opacity: 1;
    }

    /* dropdown label header */
    .dropdown-label {
      padding: 14px 22px 6px;
      font-family: 'Cormorant Garamond', serif;
      font-size: 13px;
      font-style: italic;
      font-weight: 400;
      color: var(--taupe);
      border-bottom: 1px solid var(--linen);
      margin-bottom: 4px;
    }

    /* â”€â”€â”€ HERO PLACEHOLDER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€  */
    .hero {
      height: 320px;
      background: linear-gradient(135deg, var(--linen) 0%, var(--cream) 60%, var(--taupe) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 12px;
    }
    .hero-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(36px, 6vw, 64px);
      font-weight: 300;
      letter-spacing: 0.2em;
      color: var(--deep);
      text-transform: uppercase;
    }
    .hero-sub {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 11px;
      font-weight: 200;
      letter-spacing: 0.45em;
      color: var(--warm-gray);
      text-transform: uppercase;
    }
    .hero-rule {
      width: 60px;
      height: 1px;
      background: var(--gold);
      margin: 6px 0;
    }

    /* Layout Generale */
.container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.section-title {
    font-size: 2rem;
    color: #333;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Grid System */
.coin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
}

/* Card Style */
.coin-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.coin-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.coin-image {
    position: relative;
    height: 200px;
    background: #f9f9f9;
}

.coin-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #d4af37; /* Colore Oro */
    color: white;
    padding: 4px 10px;
    font-size: 0.75rem;
    border-radius: 4px;
    font-weight: bold;
}

/* Info Moneta */
.coin-info {
    padding: 20px;
}

.coin-name {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
    color: #222;
}

.coin-user {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 20px;
}

.coin-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f0f0f0;
    padding-top: 15px;
}

.coin-price {
    font-weight: bold;
    font-size: 1.2rem;
    color: #2c3e50;
}

.btn-view {
    text-decoration: none;
    font-size: 0.8rem;
    color: #d4af37;
    border: 1px solid #d4af37;
    padding: 6px 12px;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-view:hover {
    background: #d4af37;
    color: #fff;
}

details.coin-details {
  background: #f8f4ec;
  border: 1px solid #e5d8c2;
  border-radius: 8px;
  padding: 12px;
  margin-top: 12px;
}

details.coin-details summary {
  list-style: none;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 700;
  color: #b89a6a;
  margin-bottom: 10px;
}

details.coin-details summary::-webkit-details-marker {
  display: none;
}

details.coin-details p {
  margin: 8px 0 0;
  color: #4a4a4a;
  font-size: 0.95rem;
}
  </style>
</head>
<body>

  
  <div class="top-bar">
    <?php if (isset($_SESSION['user_id'])): ?>
      <span class="user-welcome">Benvenuto, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
      <form method="post" action="logout.php" style="margin-left: auto;">
        <button type="submit" class="btn-login">Logout</button>
      </form>
    <?php else: ?>
      <form method="post" action="login.php" style="margin-left: auto;">
        <button type="submit" class="btn-login">Accedi</button>
      </form>
      <form method="post" action="register.php">
        <button type="submit" class="btn-signup">Registrati</button>
      </form>
    <?php endif; ?>
</div>


  <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
       MAIN HEADER  â€“  Logo + Navigation
  â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
  <header class="main-header">
    <div class="header-inner">

      <!-- LOGO -->
      <a href="index.php" class="logo">
        <span class="logo-word">CoinColl </span>
        <span class="logo-sub">Storia nel Metallo</span>
      </a>

      <div class="divider"></div>

      <!-- NAVIGATION -->
      <nav class="main-nav" aria-label="Menu principale">
        <ul>

          <!-- HOME -->
          <li>
            <a href="index.php" class="active">Home</a>
          </li>

          <!-- ANNI -->
          <!-- INTEGRAZIONE PHP/SQL: Le voci di questo menu possono essere generate dinamicamente: -->
            
          <li class="has-dropdown">
            <a href="#">Anni</a>
            <div class="dropdown">
              <div class="dropdown-label">Epoche</div>
                <ul>
                  <li><a href="index.php?cat=Euro-Italia">Euro Italia</a></li>
                  <li><a href="index.php?cat=Repubblica-Italiana">Repubblica Italiana</a></li>
                  <li><a href="index.php?cat=Regno-d%27Italia-%281861-1922%29">Regno d'Italia (1861-1922)</a></li>
                  <li><a href="index.php?cat=Regno-d%27Italia-%281922-1943%29">Regno d'Italia (1922-1943)</a></li>
                  <li><a href="index.php?cat=Regno-d%27Italia-%281943-1946%29">Regno d'Italia (1943-1946)</a></li>
                </ul>
            </div>
          </li>

          <?php if ($cat === 'Euro-Italia'): ?>
            <li><a href="anni.php?cat=Euro-Italia">Euro Italia</a></li>
          <?php endif; ?>
          <!-- MATERIALE -->
          <li class="has-dropdown">
            <a href="#">Materiale</a>
            <div class="dropdown">
              <div class="dropdown-label">Materiale Moneta</div>
              <ul>
               <li><a href="index.php?tipo=argento"><span class="dot"></span>Argento</a></li>
                <li><a href="index.php?tipo=oro"><span class="dot"></span>Oro</a></li>
                <li><a href="index.php?tipo=nickel"><span class="dot"></span>Altro</a></li>
              </ul>
            </div>
          </li>

          
        </ul>
      </nav>
      <!-- end nav -->

    </div>
  </header>


  <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
       HERO â€“ placeholder pagina
  â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
  <section class="hero">
    <p class="hero-sub">La storia è un'arte</p>
    <div class="hero-rule"></div>
    <h1 class="hero-title">CoinColl</h1>
  </section>

  <main class="container">
    <?php if (isset($_SESSION['user_id'])): ?>
      <section class="add-coin-section">
        <h2 class="add-coin-title">Aggiungi una Nuova Moneta</h2>

        <?php if ($addError): ?>
          <div class="message error"><?= htmlspecialchars($addError) ?></div>
        <?php endif; ?>

        <?php if ($addSuccess): ?>
          <div class="message success"><?= htmlspecialchars($addSuccess) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php" class="add-coin-form">
          <div class="form-group">
            <label for="nome">Nome della Moneta</label>
            <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="prezzo">Prezzo (€)</label>
            <input type="number" id="prezzo" name="prezzo" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['prezzo'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label for="materiale">Materiale</label>
            <select id="materiale" name="materiale" required>
              <option value="">Seleziona materiale</option>
              <option value="Argento" <?= (isset($_POST['materiale']) && $_POST['materiale'] === 'Argento') ? 'selected' : '' ?>>Argento</option>
              <option value="Oro" <?= (isset($_POST['materiale']) && $_POST['materiale'] === 'Oro') ? 'selected' : '' ?>>Oro</option>
              <option value="Rame" <?= (isset($_POST['materiale']) && $_POST['materiale'] === 'Rame') ? 'selected' : '' ?>>Rame</option>
              <option value="Nickel" <?= (isset($_POST['materiale']) && $_POST['materiale'] === 'Nickel') ? 'selected' : '' ?>>Nickel</option>
              <option value="Altro" <?= (isset($_POST['materiale']) && $_POST['materiale'] === 'Altro') ? 'selected' : '' ?>>Altro</option>
            </select>
          </div>

          <div class="form-group">
            <label for="anno_emi">Anno di Emissione</label>
            <input type="number" id="anno_emi" name="anno_emi" min="1000" max="2100" required value="<?= htmlspecialchars($_POST['anno_emi'] ?? '') ?>">
          </div>

          <button type="submit" name="add_coin" value="1" class="add-coin-btn">Aggiungi Moneta</button>
        </form>
      </section>
    <?php endif; ?>
    <div class="section-header">
        <h2 class="section-title">Ultime Inserzioni</h2>
        <div class="hero-rule" style="margin: 10px 0 30px 0; width: 50px;"></div>
    </div>

    <div class="coin-grid">
      <?php foreach ($monete as $moneta): ?>
        <article class="coin-card">
            <div class="coin-image">
                <img src="https://via.placeholder.com/300x200" alt="Moneta ">
                <span class="badge">Raro</span>
            </div>
            <div class="coin-info">
                <h3 class="coin-name"><?= htmlspecialchars($moneta['nome']) ?></h3>
                <p class="coin-user">Pubblicato da: <strong><?= htmlspecialchars($moneta['autore']) ?></strong></p>
                <div class="coin-footer">
                    <span class="coin-price"> € <?= number_format($moneta['prezzo'], 2, ',', '.') ?></span>
                      <details class="coin-details">
                        <summary>Vedi dettagli</summary>
                        <p><strong>Materiale:</strong> <?= htmlspecialchars($moneta['Materiale']) ?></p>
                        <p><strong>Anno di emissione:</strong> <?= htmlspecialchars($moneta['AnnoEmi']) ?></p>
                      </details>
                </div>
            </div>
        </article>
      <?php endforeach; ?>

    

        </div>
      </main>


</body>
</html>






















