<?php
session_start();
if (!isset($_SESSION['username'])) {
  header("Location: login.html");
  exit();
}
include 'lang.php';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'en' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $lang['upload_title'] ?> | Voice Shield</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(145deg, #060617 0%, #0b1124 40%, #1a1635 100%);
      color: #e5e7eb;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .card {
      background: rgba(255, 255, 255, 0.04);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      box-shadow: 0 0 40px -10px rgba(124, 58, 237, 0.4);
      transition: all 0.3s ease;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 0 60px -10px rgba(124, 58, 237, 0.6);
    }

    .upload-zone {
      border: 2px dashed rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }

    .upload-zone:hover {
      border-color: #7c3aed;
      background: rgba(124, 58, 237, 0.1);
    }

    .glow-btn {
      background: linear-gradient(90deg, #2563eb, #7c3aed);
      box-shadow: 0 0 25px rgba(124, 58, 237, 0.3);
      transition: all 0.3s ease;
    }

    .glow-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 0 35px rgba(124, 58, 237, 0.6);
    }

    .page-top-spacing {
      padding-top: 6.5rem;
    }

    @media (min-width: 1024px) {
      .page-top-spacing { padding-top: 8rem; }
    }
  </style>
</head>

<body>
  <?php include("includes/header.php"); ?>

  <main class="flex items-start justify-center min-h-[80vh] px-4 page-top-spacing">
    <div class="card rounded-2xl p-10 max-w-md w-full text-center">

      <div class="relative mx-auto w-24 h-24 mb-6 flex items-center justify-center rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 shadow-[0_0_40px_-10px_rgba(124,58,237,0.7)]">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 10h1v4H4v-4zm3 0h1v4H7v-4zm3-2h1v8h-1V8zm3 1h1v6h-1V9zm3-2h1v10h-1V7zm3 3h1v4h-1v-4z"/>
        </svg>
        <div class="absolute inset-0 rounded-full bg-gradient-to-tr from-indigo-400/50 to-purple-400/50 blur-xl opacity-70"></div>
      </div>

      <h2 class="text-2xl font-bold text-white mb-2"><?= $lang['upload_heading'] ?></h2>
      <p class="text-sm text-gray-400 mb-6"><?= $lang['upload_subtext'] ?? 'Upload your voice file for phishing analysis.' ?></p>

      <form action="upload_process.php" method="POST" enctype="multipart/form-data" class="space-y-5" id="uploadForm">
        <label for="audioFile" class="upload-zone rounded-xl p-6 block cursor-pointer">
          <p class="text-sm text-gray-300">🎤 Drag & Drop your file here or click to choose</p>
          <p class="text-xs text-gray-500 mt-1">Accepted: Any audio format</p>
          <input type="file" name="audioFile" id="audioFile" accept="audio/*" required class="hidden">
        </label>

        <button type="submit" class="glow-btn w-full py-3 rounded-lg text-white font-semibold" id="analyzeBtn">
          🚀 <?= $lang['upload_button'] ?>
        </button>
      </form>
    </div>
  </main>

  <footer class="text-center text-gray-500 text-sm py-6 border-t border-white/10 mt-10">
    © <?= date('Y') ?> Voice Shield — Secure voice, secure future
  </footer>

  <script>
    const dropZone = document.querySelector('.upload-zone');
    const input = document.getElementById('audioFile');
    const form = document.getElementById('uploadForm');
    const analyzeBtn = document.getElementById('analyzeBtn');


    dropZone.addEventListener('dragover', e => {
      e.preventDefault();
      dropZone.classList.add('border-purple-400', 'bg-white/10');
    });

    dropZone.addEventListener('dragleave', e => {
      e.preventDefault();
      dropZone.classList.remove('border-purple-400', 'bg-white/10');
    });

    dropZone.addEventListener('drop', e => {
      e.preventDefault();
      input.files = e.dataTransfer.files;
      dropZone.classList.remove('border-purple-400', 'bg-white/10');
      if (input.files.length > 0) {
        analyzeBtn.disabled = true;
        analyzeBtn.innerText = '⏳ Processing...';
        form.submit();
      }
    });

    input.addEventListener('change', () => {
      if (input.files.length > 0) {
        analyzeBtn.disabled = true;
        analyzeBtn.innerText = '⏳ Processing...';
        form.submit();
      }
    });
  </script>
</body>
</html>
