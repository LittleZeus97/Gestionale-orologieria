<?php
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Compila tutti i campi.';
    } else {
        // Qui puoi inserire la logica di autenticazione con database
        // ad esempio: verifica email/password, avvia sessione, redirect, ecc.
        $error = 'Funzionalità di login non ancora implementata.';
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Accedi — CoinColl</title>
  <style>
    :root {
      --cream: #F5F2ED;
      --deep: #1E1A17;
      --gold: #B89A6A;
      --gold-lt: #D4B98A;
      --charcoal: #3A3530;
      --linen: #EDE8DF;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      font-family: 'Josefin Sans', sans-serif;
      background: var(--cream);
      color: var(--charcoal);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .auth-card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border: 1px solid var(--linen);
      border-radius: 12px;
      box-shadow: 0 18px 40px rgba(30,26,23,0.08);
      padding: 32px;
    }
    .auth-card h1 {
      font-family: 'Cormorant Garamond', serif;
      margin-bottom: 24px;
      font-size: 2rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--deep);
    }
    .field {
      display: grid;
      gap: 8px;
      margin-bottom: 18px;
    }
    label {
      font-size: 0.85rem;
      color: var(--deep);
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }
    input {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid var(--linen);
      border-radius: 8px;
      font-size: 0.95rem;
      font-family: inherit;
      background: #faf7f2;
      color: var(--charcoal);
    }
    button {
      width: 100%;
      padding: 12px 16px;
      background: var(--gold);
      border: 1px solid var(--gold);
      border-radius: 8px;
      color: var(--deep);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      cursor: pointer;
    }
    button:hover {
      background: var(--gold-lt);
    }
    .auth-footer {
      margin-top: 18px;
      text-align: center;
      font-size: 0.9rem;
      color: var(--charcoal);
    }
    .auth-footer a {
      color: var(--deep);
      text-decoration: none;
      font-weight: 700;
    }
    .message {
      margin-bottom: 18px;
      padding: 12px 14px;
      border-radius: 8px;
      font-size: 0.95rem;
    }
    .error { background: #f9d6d1; color: #7f221b; border: 1px solid #f0b4af; }
  </style>
</head>
<body>
  <main class="auth-card">
    <h1>Accedi</h1>

    <?php if ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required />
      </div>
      <button type="submit">Accedi</button>
    </form>
    <div class="auth-footer">
      Non hai un account? <a href="register.php">Registrati</a>
    </div>
  </main>
</body>
</html>