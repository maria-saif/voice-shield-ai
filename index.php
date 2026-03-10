<?php
session_start();
include 'lang.php';
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Voice Shield</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: radial-gradient(circle at top left, #111827, #1e293b, #0f172a);
      color: #d1d5db;
      overflow-x: hidden;
      transition: all 0.4s ease;
    }

    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 200%;
      height: 200%;
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
      box-shadow: 0 0 20px rgba(124, 58, 237, 0.3);
      transition: all 0.3s ease-in-out;
      position: relative;
      overflow: hidden;
    }

    .btn-glow::after {
      content: "";
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(120deg, rgba(255,255,255,0.3) 0%, transparent 80%);
      transition: all 0.5s;
    }

    .btn-glow:hover::after { left: 100%; }
    .btn-glow:hover {
      transform: translateY(-3px) scale(1.03);
      box-shadow: 0 0 35px rgba(124, 58, 237, 0.6);
    }

    .fade-in {
      opacity: 0;
      transform: translateY(20px);
      animation: fadeInUp 1.2s ease forwards;
    }

    @keyframes fadeInUp {
      to { opacity: 1; transform: translateY(0); }
    }

    .glow-bg {
      position: absolute;
      width: 22rem;
      height: 22rem;
      background: radial-gradient(circle, rgba(59,130,246,0.5), rgba(124,58,237,0.25), transparent);
      border-radius: 50%;
      filter: blur(100px);
      z-index: -1;
      animation: pulse 6s ease-in-out infinite alternate;
    }

    @keyframes pulse {
      from { transform: scale(1); opacity: 0.8; }
      to { transform: scale(1.2); opacity: 0.6; }
    }
  </style>
</head>

<body class="min-h-screen">

  <?php include 'includes/header.php'; ?>

  <div class="pt-28 md:pt-32 lg:pt-40"></div>

  <section class="flex flex-col-reverse md:flex-row items-center justify-between px-8 py-20 max-w-7xl mx-auto fade-in">
    
    <div class="w-full md:w-1/2 text-center md:text-left mt-10 md:mt-0 space-y-6">
      <h2 class="text-5xl font-extrabold bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-500 text-transparent bg-clip-text drop-shadow-xl leading-tight">
        <?= $lang['home_title'] ?>
      </h2>

      <p class="text-lg text-gray-300 leading-relaxed max-w-md mx-auto md:mx-0">
        <?= $lang['home_paragraph'] ?>
      </p>

      <a href="login.php" class="btn-glow inline-block px-10 py-4 text-lg font-semibold rounded-lg text-white shadow-md">
        <?= $lang['home_button'] ?>
      </a>
    </div>

    <div class="w-full md:w-1/2 relative flex justify-center">
      <div class="glow-bg"></div>
      <img src="images/hero-ai.jpg" 
           alt="AI Voice Detection"
           class="rounded-2xl shadow-2xl border border-gray-700 max-w-md transform hover:scale-105 transition duration-700 ease-in-out">
    </div>

  </section>

  <section class="relative bg-[#0f172a] py-24">
    <div class="max-w-7xl mx-auto px-6 text-center fade-in">

      <h2 class="text-4xl md:text-5xl font-bold mb-8 bg-gradient-to-r from-blue-400 to-purple-500 text-transparent bg-clip-text">
        <?= $lang['features_title'] ?>
      </h2>

      <p class="text-gray-400 max-w-2xl mx-auto mb-16 text-lg">
        <?= $lang['features_desc'] ?>
      </p>

      <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">

        <div class="bg-gray-900/40 backdrop-blur-sm border border-gray-700 rounded-2xl p-8 hover:-translate-y-2 transition-all hover:shadow-xl hover:shadow-blue-500/10">
          <h3 class="text-xl font-semibold mb-2 text-white"><?= $lang['feature_voice_auth'] ?></h3>
          <p class="text-gray-400 text-sm leading-relaxed">
            <?= $lang['feature_voice_auth_desc'] ?>
          </p>
        </div>

        <div class="bg-gray-900/40 backdrop-blur-sm border border-gray-700 rounded-2xl p-8 hover:-translate-y-2 transition-all hover:shadow-xl hover:shadow-purple-500/10">
          <h3 class="text-xl font-semibold mb-2 text-white"><?= $lang['feature_fraud_ai'] ?></h3>
          <p class="text-gray-400 text-sm leading-relaxed">
            <?= $lang['feature_fraud_ai_desc'] ?>
          </p>
        </div>

        <div class="bg-gray-900/40 backdrop-blur-sm border border-gray-700 rounded-2xl p-8 hover:-translate-y-2 transition-all hover:shadow-xl hover:shadow-indigo-500/10">
          <h3 class="text-xl font-semibold mb-2 text-white"><?= $lang['feature_behavior'] ?></h3>
          <p class="text-gray-400 text-sm leading-relaxed">
            <?= $lang['feature_behavior_desc'] ?>
          </p>
        </div>

      </div>
    </div>
  </section>

  <footer class="mt-16 py-8 border-t border-gray-800 text-center text-gray-500 text-sm">
    © <?= date('Y') ?> Voice Shield — All Rights Reserved.
  </footer>

</body>
</html>
