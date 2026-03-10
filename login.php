<?php
session_start();
include 'lang.php';

function t($key, $fallback = '') {
  global $lang;
  return $lang[$key] ?? $fallback;
}

$error_message = '';
if (isset($_GET['error'])) {
  if ($_GET['error'] === 'invalid') {
    $error_message = t('login_error_invalid', 'Invalid email or password.');
  } elseif ($_GET['error'] === 'empty') {
    $error_message = t('login_error_empty', 'Please fill in all fields.');
  } else {
    $error_message = t('login_error_unknown', 'Something went wrong. Please try again.');
  }
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= t('login_title', 'Login') ?> - Voice Shield</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: radial-gradient(circle at top left, #111827, #1e293b, #0f172a);
      color: #e2e8f0;
      overflow-x: hidden;
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 200%; height: 200%;
      background: radial-gradient(circle at 30% 30%, rgba(59,130,246,0.15), transparent 70%),
                  radial-gradient(circle at 70% 70%, rgba(124,58,237,0.15), transparent 70%);
      animation: moveBg 12s infinite alternate ease-in-out;
      z-index: -2;
    }
    @keyframes moveBg {
      0% { transform: translate(0, 0) scale(1); }
      100% { transform: translate(-10%, -10%) scale(1.1); }
    }
    .btn-glow {
      background: linear-gradient(90deg, #2563eb, #7c3aed);
      transition: all 0.3s ease-in-out;
      box-shadow: 0 0 20px rgba(124, 58, 237, 0.3);
    }
    .btn-glow:hover {
      transform: translateY(-3px) scale(1.03);
      box-shadow: 0 0 35px rgba(124, 58, 237, 0.6);
    }
    .input-field {
      background-color: #1e293b;
      border: 1px solid #334155;
      color: #e2e8f0;
      transition: all 0.3s ease;
    }
    .input-field:focus {
      border-color: #6366f1;
      box-shadow: 0 0 10px rgba(99,102,241,0.3);
      outline: none;
    }
  </style>
</head>

<body class="min-h-screen">

  <?php include 'includes/header.php'; ?>

  <section class="flex flex-col items-center justify-center py-24 fade-in">
    <div class="bg-gray-900/60 border border-gray-700 rounded-2xl p-10 shadow-xl w-full max-w-md backdrop-blur-md">

      <h2 class="text-2xl font-semibold text-center mb-6 text-white">
        <?= t('login_title', 'Login to Your Account') ?>
      </h2>

      <?php if (!empty($error_message)): ?>
        <div class="mb-4 p-3 text-sm text-red-400 bg-red-900/30 rounded-md border border-red-600 text-center">
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <form action="login_process.php" method="POST" class="space-y-5">

        <div>
          <label class="block mb-1 text-sm text-gray-300"><?= t('login_email', 'Email') ?></label>
          <input type="email" name="email" required class="input-field w-full px-4 py-2 rounded-md" />
        </div>

        <div>
          <label class="block mb-1 text-sm text-gray-300"><?= t('login_password', 'Password') ?></label>
          <input type="password" name="password" required class="input-field w-full px-4 py-2 rounded-md" />
        </div>

        <button type="submit" class="btn-glow w-full py-2 text-lg font-semibold rounded-md text-white">
          <?= t('login_button', 'Login') ?>
        </button>

        <div class="text-center mt-4">
          <!-- If you don't have forgot password feature, keep it as text or point to a placeholder page -->
          <a href="forgot_password.php" class="text-blue-400 hover:underline text-sm">
            <?= t('login_forgot', 'Forgot password?') ?>
          </a>
        </div>
      </form>

      <p class="mt-6 text-center text-sm text-gray-400">
        <?= t('login_no_account', "Don't have an account?") ?>
        <a href="register.php" class="text-blue-400 hover:underline"><?= t('login_register', 'Register') ?></a>
      </p>

    </div>
  </section>

  <footer class="mt-12 py-8 border-t border-gray-800 text-center text-gray-500 text-sm">
    © <?= date('Y') ?> Voice Shield — All Rights Reserved.
  </footer>

</body>
</html>