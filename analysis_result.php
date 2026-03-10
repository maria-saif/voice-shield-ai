<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$text = $_SESSION['transcribed_text'] ?? '';
if (empty($text)) {
    echo "<h2 style='color:red;text-align:center;'>No transcribed text found!</h2>";
    exit;
}

$audio_path = $_SESSION['uploaded_audio_path'] ?? '';


require_once __DIR__ . "/risk_engine.php";
$weights = require __DIR__ . "/keywords_weights.php";

$analysis = vs_analyze_text($text, $weights);

$found_keywords = $analysis["keywords"];
$risk_level     = $analysis["risk"];
$risk_label     = $analysis["category"];
$actions_text   = $analysis["actions"];

// =========================================
// 🎨 تحديد اللون مثل تصميمك
// =========================================
if ($risk_level >= 75) {
    $risk_class = 'bg-red-600/80 border-red-400';
} elseif ($risk_level >= 40) {
    $risk_class = 'bg-yellow-500/80 border-yellow-300';
} elseif ($risk_level >= 15) {
    $risk_class = 'bg-yellow-300/80 border-yellow-200';
} else {
    $risk_class = 'bg-green-600/80 border-green-400';
}

$_SESSION['risk_level']     = $risk_level;
$_SESSION['risk_label']     = $risk_label;
$_SESSION['found_keywords'] = $found_keywords;
$_SESSION['actions']        = $actions_text;


$send_email_path = __DIR__ . '/send_email.php';
if (file_exists($send_email_path)) {
    $_POST = [
        'filename'       => $_SESSION['uploaded_filename'] ?? 'Unknown',
        'risk_level'     => $risk_level,
        'risk_label'     => $risk_label,
        'found_keywords' => implode(', ', $found_keywords),
        'text'           => $text
    ];
    require $send_email_path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Analysis Result | Voice Shield</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
<style>
 body{font-family:'Poppins',sans-serif;background:linear-gradient(160deg,#0b1124 0%,#1a1635 60%,#2a1b59 100%);color:#e5e7eb;min-height:100vh;overflow-x:hidden;}
 .card{background:rgba(255,255,255,0.05);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,0.08);box-shadow:0 0 35px -8px rgba(124,58,237,0.4);}
 .glow-btn{background:linear-gradient(90deg,#2563eb,#7c3aed);box-shadow:0 0 20px rgba(124,58,237,0.4);transition:all .3s ease;}
 .glow-btn:hover{transform:scale(1.05);box-shadow:0 0 30px rgba(124,58,237,0.6);}
</style>
</head>
<body>
<?php include("includes/header.php"); ?>

<main class="flex items-start justify-center px-4 pt-32 pb-12">
<div class="card rounded-2xl p-10 max-w-3xl w-full text-center">

<h1 class="text-3xl font-bold mb-6 bg-gradient-to-r from-indigo-400 to-purple-500 bg-clip-text text-transparent">
 🧠 Analysis Result
</h1>

<h2 class="text-xl font-semibold text-gray-200 mb-3">🎙️ Transcribed Text</h2>
<div class="bg-gray-800/40 p-4 rounded-xl border border-gray-700 text-left text-gray-300 whitespace-pre-wrap">
 <?= nl2br(htmlspecialchars($text)) ?>
</div>

<?php if (!empty($audio_path) && file_exists($audio_path)): ?>
  <h2 class="text-xl font-semibold text-gray-200 mt-8 mb-2">🎧 Original Audio</h2>
  <div class="bg-gray-800/40 p-4 rounded-xl border border-gray-700 text-left text-gray-300">
    <audio controls class="w-full rounded-lg mt-1">
      <source src="<?= htmlspecialchars($audio_path) ?>" type="audio/mpeg">
    </audio>
  </div>
<?php endif; ?>

<h2 class="text-xl font-semibold text-gray-200 mt-8 mb-2">⚠️ Risk Level</h2>
<div class="w-full bg-gray-700/50 rounded-full h-6 mb-4 overflow-hidden">
  <div class="h-6 text-xs font-semibold text-white flex items-center justify-center transition-all duration-700"
       style="width: <?= min($risk_level,100) ?>%;background:linear-gradient(90deg,#7c3aed,#2563eb);">
    <?= htmlspecialchars($risk_level) ?>%
  </div>
</div>

<div class="p-4 rounded-xl font-bold text-lg text-white border <?= $risk_class ?>">
  <?= htmlspecialchars($risk_label) ?>
</div>

<div class="mt-4 text-gray-300 text-sm">
  <?= htmlspecialchars($actions_text) ?>
</div>

<?php if (!empty($found_keywords)): ?>
<div class="mt-6 text-left">
  <h3 class="text-lg font-semibold text-gray-200 mb-2">🔍 Detected Suspicious Keywords:</h3>
  <div class="bg-yellow-100/10 text-yellow-300 p-3 rounded-xl border border-yellow-500/40">
    <?= htmlspecialchars(implode(', ', $found_keywords)) ?>
  </div>
</div>
<?php endif; ?>

<div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
  <a href="dashboard.php" class="glow-btn px-6 py-2 rounded-lg text-white font-semibold">← Back to Dashboard</a>

  <form action="generate_pdf.php" method="post">
    <button type="submit" class="glow-btn px-6 py-2 rounded-lg text-white font-semibold">📄 Download PDF Report</button>
  </form>
</div>

</div>
</main>

<footer class="text-center text-gray-500 text-sm py-6 border-t border-white/10 mt-10">
 © <?= date('Y') ?> Voice Shield — AI Phishing Detection
</footer>
</body>
</html>
