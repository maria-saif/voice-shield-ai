<?php
session_start();
require_once "includes/db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = (int)$_SESSION['user_id'];


$filter_result = $_GET['result'] ?? '';
$from_date     = $_GET['from_date'] ?? '';
$to_date       = $_GET['to_date'] ?? '';

$sql    = "SELECT filename, result, risk_level, suspicious_keywords, created_at 
           FROM analysis_history WHERE user_id = ?";
$params = [$user_id];
$types  = "i";

if ($filter_result && in_array($filter_result, ['Safe', 'Suspicious'])) {
    $sql .= " AND result = ?";
    $params[] = $filter_result; 
    $types .= "s";
}
if (!empty($from_date)) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $from_date; 
    $types .= "s";
}
if (!empty($to_date)) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $to_date; 
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();


$rows = [];
$total = 0; 
$safe = 0; 
$susp = 0; 
$risk_sum = 0;

while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
    $total++;
    $risk_sum += (int)$r['risk_level'];
    if ($r['result'] === 'Suspicious') $susp++;
    else $safe++;
}

$avg_risk = $total ? round($risk_sum / $total, 1) : 0;

include 'lang.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>My Analysis | Voice Shield</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet" />
<style>
    :root{ --vio:#7c3aed; --blu:#2563eb; }
    body{
      font-family:'Poppins',sans-serif;
      background: radial-gradient(900px 500px at 10% 15%, rgba(124,58,237,.12), transparent 30%),
                  radial-gradient(800px 500px at 95% 90%, rgba(37,99,235,.12), transparent 32%),
                  linear-gradient(160deg,#060617 0%, #0b1124 45%, #1a1635 100%);
      color:#e5e7eb; min-height:100vh; overflow-x:hidden;
    }
    .page-top { padding-top: 8.5rem; }
    @media(min-width:1024px){ .page-top { padding-top: 9.5rem; } }

    .glass{ background: rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); backdrop-filter: blur(12px); border-radius:16px; }
    .glow{ box-shadow: 0 0 42px -12px rgba(124,58,237,.55); }
    .title-shine{
      background: linear-gradient(90deg,#c4b5fd 0%,#a78bfa 25%,#60a5fa 75%,#c4b5fd 100%);
      -webkit-background-clip:text; background-clip:text; color:transparent;
      animation: shine 4.5s linear infinite;
      background-size: 200% auto;
    }
    @keyframes shine { 0%{background-position:0% 50%} 100%{background-position:200% 50%} }

    .thead-grad{ background: linear-gradient(90deg, rgba(37,99,235,.35), rgba(124,58,237,.35)); }
    .row-hover:hover{ background: rgba(255,255,255,0.06); }

    .riskbar{ height: 10px; border-radius:999px; overflow:hidden; background: rgba(255,255,255,.12); }
    .riskfill{ height:100%; border-radius:999px; background: linear-gradient(90deg,var(--blu),var(--vio)); }

    .chip{
        padding:.25rem .55rem; 
        border-radius:999px; 
        font-size:.72rem; 
        margin:.15rem;
        background: rgba(124,58,237,.16); 
        border:1px solid rgba(124,58,237,.35); 
        color:#c4b5fd; 
        white-space:nowrap;
    }

    .badge-safe{ color:#86efac; background:rgba(22,163,74,.18); border:1px solid rgba(34,197,94,.45);}
    .badge-susp{ color:#fca5a5; background:rgba(239,68,68,.18); border:1px solid rgba(239,68,68,.45);}
</style>
</head>
<body>

<?php include "includes/header.php"; ?>

<main class="page-top px-4 pb-16">
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
      <div class="w-6 h-6 rounded-md" style="background:linear-gradient(135deg,var(--blu),var(--vio)); box-shadow:0 0 28px rgba(124,58,237,.7)"></div>
      <h1 class="text-3xl md:text-4xl font-extrabold title-shine">My Analysis History</h1>
    </div>

    <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass glow p-4 rounded-2xl">
            <div class="text-sm text-slate-300">Total</div>
            <div class="text-2xl font-bold"><?= number_format($total) ?></div>
        </div>
        <div class="glass glow p-4 rounded-2xl">
            <div class="text-sm text-slate-300">Safe / Suspicious</div>
            <div class="text-2xl font-bold">
                <span class="text-emerald-400"><?= number_format($safe) ?></span>
                <span class="text-slate-400"> / </span>
                <span class="text-rose-400"><?= number_format($susp) ?></span>
            </div>
        </div>
        <div class="glass glow p-4 rounded-2xl">
            <div class="text-sm text-slate-300">Average Risk</div>
            <div class="text-2xl font-bold"><?= $avg_risk ?>%</div>
        </div>
    </section>

    <?php if (count($rows) > 0): ?>
    <div class="overflow-x-auto glass glow rounded-2xl">
        <table class="min-w-full">
            <thead class="thead-grad text-white text-xs uppercase">
                <tr>
                    <th class="px-6 py-3 text-left">File</th>
                    <th class="px-6 py-3 text-left">Result</th>
                    <th class="px-6 py-3 text-left">Risk</th>
                    <th class="px-6 py-3 text-left">Suspicious Keywords</th>
                    <th class="px-6 py-3 text-left">Date</th>
                    <th class="px-6 py-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody class="text-sm divide-y divide-white/10">
                <?php foreach ($rows as $row): ?>
                <?php
                    $file = htmlspecialchars($row['filename'] ?? '');
                    $risk = (int)$row['risk_level'];
                    $kw   = json_decode($row['suspicious_keywords'], true) ?? [];
                    $isSusp = ($row['result'] === 'Suspicious');

                    $uploadPath = '/phishing/uploads/audio/' . $file;
                    $canPlay = file_exists(__DIR__ . '/uploads/audio/' . $file);
                ?>

                <tr class="row-hover">
                    <td class="px-6 py-4 text-slate-200"><?= $file ?: "<i>No file</i>" ?></td>

                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded border text-xs <?= $isSusp ? 'badge-susp' : 'badge-safe' ?>">
                           <?= htmlspecialchars($row['result'] ?? '') ?>
                        </span>
                    </td>

                    <td class="px-6 py-4">
                        <div class="riskbar w-44">
                            <div class="riskfill" style="width: <?= max(0,min($risk,100)) ?>%"></div>
                        </div>
                        <div class="mt-1 text-xs text-slate-300"><?= $risk ?>%</div>
                    </td>

                    <td class="px-6 py-4">
                        <?php if (!empty($kw)): ?>
                        <div class="flex flex-wrap max-w-[420px]">
                            <?php foreach ($kw as $k): ?>
                                <span class="chip"><?= htmlspecialchars($k ?? '') ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                            <span class="text-slate-400 italic">None</span>
                        <?php endif; ?>
                    </td>

                    <td class="px-6 py-4 text-slate-300">
                        <?= htmlspecialchars($row['created_at'] ?? '') ?>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <?php if ($canPlay): ?>
                                <button class="px-3 py-1 border border-white/15 rounded hover:bg-white/10 text-slate-200 text-xs"
                                        onclick="openPlayer('<?= $uploadPath ?>')">▶ Play</button>
                            <?php else: ?>
                                <span class="text-slate-500 text-xs">No audio</span>
                            <?php endif; ?>

                            <a class="px-3 py-1 border border-white/15 rounded hover:bg-white/10 text-slate-200 text-xs" 
                               href="generate_pdf.php?file=<?= urlencode($file) ?>">PDF</a>
                        </div>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
        <div class="glass glow p-10 text-center rounded-2xl">
            <div class="text-2xl font-semibold mb-2">No results found</div>
            <p class="text-slate-300">Try different filters or upload a new analysis.</p>
        </div>
    <?php endif; ?>

</div>
</main>

<dialog id="playerDlg" class="backdrop:bg-black/60 rounded-2xl p-0">
  <div class="bg-[#0b1124] text-slate-100 p-6 w-[320px] glass">
      <div class="text-lg font-semibold mb-3">Audio Preview</div>
      <audio id="audioEl" controls class="w-full"></audio>
      <div class="mt-4 text-right">
        <button onclick="closePlayer()" class="px-4 py-2 border border-white/15 rounded hover:bg-white/10">Close</button>
      </div>
  </div>
</dialog>

<footer class="text-center text-slate-400 text-sm py-6 border-t border-white/10">
    © <?= date('Y') ?> Voice Shield — My Analysis
</footer>

<script>
const dlg = document.getElementById('playerDlg');
const audioEl = document.getElementById('audioEl');

function openPlayer(src){
    audioEl.src = src;
    audioEl.play();
    dlg.showModal();
}

function closePlayer(){
    audioEl.pause();
    dlg.close();
}
</script>

</body>
</html>
