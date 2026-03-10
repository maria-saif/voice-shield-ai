<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================
// 🔊 النص المحوّل من الصوت
// =========================================
$text = $_SESSION['transcribed_text'] ?? '';
if (empty($text)) {
    echo "<h2 style='color:red;text-align:center;'>No transcribed text found!</h2>";
    exit;
}

$audio_path = $_SESSION['uploaded_audio_path'] ?? '';

// =========================================
// ✅ تحليل موحّد (Text Risk) من Engine
// =========================================
require_once __DIR__ . "/risk_engine.php";
$weights = require __DIR__ . "/keywords_weights.php";

$analysis = vs_analyze_text($text, $weights);

$found_keywords = $analysis["keywords"];
$risk_level     = $analysis["risk"];
$risk_label     = $analysis["category"];
$actions_text   = $analysis["actions"];

// =========================================
// 🤖 Robocall Detection
// =========================================
$normalized_text = mb_strtolower($text, 'UTF-8');
$robocall_score = 0;

if (mb_strlen($normalized_text) > 400) $robocall_score += 15;
if (mb_strlen($normalized_text) > 900) $robocall_score += 15;

$robocall_keywords = [
    "this is an automated call","do not hang up","press 1","press one",
    "your account has been suspended","this is an automated message",
    "هدة مكالمة آلية","اضغط واحد","اتصل الآن","عرض خاص","لقد ربحت"
];

foreach ($robocall_keywords as $rk) {
    if (mb_stripos($normalized_text, mb_strtolower($rk,'UTF-8')) !== false) {
        $robocall_score += 15;
    }
}

if (!preg_match('/[\?\؟]/u', $normalized_text)) {
    $robocall_score += 10;
}

$robocall_score = min(100, $robocall_score);

if ($robocall_score >= 70) {
    $robocall_label = '🤖 Likely Robocall';
} elseif ($robocall_score >= 40) {
    $robocall_label = '⚠️ Possible Robocall';
} else {
    $robocall_label = '🧑‍💼 Human-like Call';
}

$_SESSION['robocall_score'] = $robocall_score;

// =========================================
// 🎤 Emotion Score (اختياري)
// =========================================
$emotion_score = $_SESSION['emotion_score'] ?? null;

if ($emotion_score !== null && is_numeric($emotion_score)) {
    if ($emotion_score >= 75)      $emotion_ui = "🔥 High Tension";
    elseif ($emotion_score >= 40)  $emotion_ui = "⚠️ Medium Tension";
    elseif ($emotion_score >= 15)  $emotion_ui = "🟡 Slight Tension";
    else                           $emotion_ui = "💤 Calm / Neutral";
} else {
    $emotion_ui    = "ℹ️ Not Available";
    $emotion_score = "N/A";
}

// =========================================
// 🧠 Overall Risk
// =========================================
$parts = [$risk_level, $robocall_score];
if ($emotion_score !== "N/A" && is_numeric($emotion_score)) {
    $parts[] = $emotion_score;
}

$overall_risk = round(array_sum($parts) / count($parts), 1);

if ($overall_risk >= 75) {
    $overall_label = "🔴 High Overall Risk";
} elseif ($overall_risk >= 40) {
    $overall_label = "🟠 Suspicious Call";
} elseif ($overall_risk >= 15) {
    $overall_label = "🟡 Low to Medium Risk";
} else {
    $overall_label = "✅ Probably Safe";
}

// =========================================
// 🧩 استخراج الجمل الخطيرة
// =========================================
$sentences = preg_split('/(?<=[.!?؟])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
$dangerous_sentences = [];

foreach ($sentences as $i => $s) {
    $s_lower = mb_strtolower($s,'UTF-8');
    $matched = [];

    foreach ($found_keywords as $kw) {
        if (mb_stripos($s_lower, mb_strtolower($kw,'UTF-8')) !== false) {
            $matched[] = $kw;
        }
    }

    if (!empty($matched)) {
        $dangerous_sentences[] = [
            "index"    => $i+1,
            "text"     => trim($s),
            "keywords" => $matched
        ];
    }
}

$dangerous_index_map = [];
foreach ($dangerous_sentences as $d) {
    $dangerous_index_map[$d['index']] = true;
}

// =========================================
// 📬 إرسال الإيميل
// =========================================
$clean_sentences = [];
foreach ($dangerous_sentences as $row) {
    $clean_sentences[] = $row['index'] . ". " . $row['text'] . " [ " . implode(", ", $row['keywords']) . " ]";
}

$_POST['filename']            = $_SESSION['uploaded_filename'] ?? 'audio_file';
$_POST['text_risk']           = $risk_level;
$_POST['text_risk_label']     = $risk_label;
$_POST['found_keywords']      = implode(", ", $found_keywords);
$_POST['overall_risk']        = $overall_risk;
$_POST['overall_label']       = $overall_label;
$_POST['robocall_score']      = $robocall_score;
$_POST['robocall_label']      = $robocall_label;
$_POST['emotion_score']       = $emotion_score;
$_POST['emotion_label']       = $emotion_ui;
$_POST['dangerous_sentences'] = implode("\n", $clean_sentences);
$_POST['transcript']          = $text;
$_POST['audio_url']           = (!empty($audio_path) ? "http://localhost:8888/" . ltrim($audio_path, "/") : "");
$_POST['actions']             = $actions_text;

require __DIR__ . "/send_email.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analysis Result | Voice Shield</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body {
  background: #020617;
  color: #e2e8f0;
  font-family: 'Poppins', sans-serif;
}
.card {
  background: rgba(15,23,42,0.92);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(148,163,184,0.25);
  padding: 30px;
  border-radius: 22px;
  margin: 30px auto;
  max-width: 1100px;
}
</style>
</head>

<body>

<div class="card">

  <h1 class="text-3xl font-bold mb-2">Call Risk Assessment</h1>
  <p class="text-slate-400 mb-6">تحليل كامل للمكالمة اعتمادًا على النص، الصوت، ونمط السلوك الاحتيالي.</p>

  <!-- Overall Risk -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <p class="text-lg font-semibold"><?= htmlspecialchars($overall_label) ?></p>
      <p class="text-slate-400 text-sm mt-2"><?= htmlspecialchars($actions_text) ?></p>
    </div>
    <div class="text-right">
      <p class="text-4xl font-bold text-indigo-300"><?= htmlspecialchars($overall_risk) ?>%</p>
      <p class="text-sm text-slate-400">Overall Risk</p>
    </div>
  </div>

  <!-- Cards -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <div class="p-4 rounded-xl border border-slate-700 bg-slate-900/40">
      <p class="text-sm text-slate-400">Text Risk</p>
      <p class="text-3xl font-bold text-sky-300"><?= htmlspecialchars($risk_level) ?>%</p>
      <p class="text-slate-400 text-sm"><?= htmlspecialchars($risk_label) ?></p>
      <p class="mt-3 text-xs text-slate-400">Suspicious Keywords: <?= count($found_keywords) ?></p>
    </div>

    <div class="p-4 rounded-xl border border-slate-700 bg-slate-900/40">
      <p class="text-sm text-slate-400">Voice Emotion</p>
      <p class="text-3xl font-bold text-fuchsia-300"><?= htmlspecialchars($emotion_score) ?></p>
      <p class="text-slate-400 text-sm"><?= htmlspecialchars($emotion_ui) ?></p>
    </div>

    <div class="p-4 rounded-xl border border-slate-700 bg-slate-900/40">
      <p class="text-sm text-slate-400">Robocall Probability</p>
      <p class="text-3xl font-bold text-pink-300"><?= htmlspecialchars($robocall_score) ?>%</p>
      <p class="text-slate-400 text-sm"><?= htmlspecialchars($robocall_label) ?></p>
    </div>

  </div>

  <!-- Dangerous Sentences -->
  <h2 class="text-xl font-bold mt-8 mb-3">Suspicious Sentences</h2>

  <?php if (!empty($dangerous_sentences)): ?>
    <table class="w-full border border-slate-700 rounded-xl overflow-hidden text-sm">
      <thead class="bg-slate-800 text-slate-300">
        <tr>
          <th class="p-2 border-slate-700">#</th>
          <th class="p-2 border-slate-700">Sentence</th>
          <th class="p-2 border-slate-700">Triggered By</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dangerous_sentences as $row): ?>
        <tr class="border-t border-slate-700 hover:bg-red-500/10">
          <td class="p-2"><?= $row['index'] ?></td>
          <td class="p-2"><?= htmlspecialchars($row['text']) ?></td>
          <td class="p-2 text-amber-300"><?= htmlspecialchars(implode(', ', $row['keywords'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="text-slate-500">لا توجد جمل خطيرة.</p>
  <?php endif; ?>

  <!-- Transcript -->
  <h2 class="text-xl font-bold mt-8 mb-3">Transcript View</h2>

  <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
    <?php foreach ($sentences as $i => $s): ?>
      <div class="p-3 rounded-xl border 
          <?= isset($dangerous_index_map[$i+1]) ? 'bg-red-500/10 border-red-500/40' : 'bg-slate-900/40 border-slate-700' ?>">
        <span class="text-slate-500 text-xs mr-2">#<?= $i+1 ?></span>
        <?= htmlspecialchars($s) ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Original Audio -->
  <?php if (!empty($audio_path) && file_exists($audio_path)): ?>
    <h2 class="text-xl font-bold mt-10 mb-3">Original Audio</h2>
    <audio controls class="w-full">
      <source src="<?= htmlspecialchars($audio_path) ?>" type="audio/mpeg">
    </audio>
  <?php endif; ?>

  <!-- Keywords -->
  <h2 class="text-xl font-bold mt-8 mb-3">Detected Keywords</h2>
  <?php if (!empty($found_keywords)): ?>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($found_keywords as $kw): ?>
        <span class="px-3 py-1 bg-yellow-500/10 text-yellow-300 border border-yellow-500/40 rounded-full text-xs">
          <?= htmlspecialchars($kw) ?>
        </span>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-slate-400">لم يتم العثور على كلمات احتيالية.</p>
  <?php endif; ?>

  <!-- Buttons -->
  <div class="mt-10 flex gap-4">
    <a href="dashboard.php" class="px-5 py-2 bg-slate-800 rounded-lg">← Back to Dashboard</a>
    <form action="generate_pdf.php" method="post">
      <button class="px-5 py-2 bg-indigo-600 rounded-lg">📄 Download PDF</button>
    </form>
  </div>

</div>

</body>
</html>
