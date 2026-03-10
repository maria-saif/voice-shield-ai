<?php
session_start();

$_SESSION['lang'] = 'en';

if (!isset($_SESSION['username'])) {
  header("Location: login.html");
  exit();
}

include 'lang.php';


require_once "includes/db_connection.php";

$q1 = $conn->query("SELECT COUNT(*) AS total FROM users");
$row1 = $q1->fetch_assoc();
$_SESSION['total_users'] = $row1['total'] ?? 0;

$q2 = $conn->query("SELECT COUNT(*) AS total FROM analysis_history");
$row2 = $q2->fetch_assoc();
$_SESSION['total_analyses'] = $row2['total'] ?? 0;

$q3 = $conn->query("SELECT suspicious_keywords FROM analysis_history 
                    WHERE suspicious_keywords IS NOT NULL AND suspicious_keywords != '[]'");

$words = [];
while ($r = $q3->fetch_assoc()) {
    $arr = json_decode($r['suspicious_keywords'], true);
    if (is_array($arr)) {
        foreach ($arr as $w) {
            $w = trim($w);
            if ($w !== "") {
                if (!isset($words[$w])) $words[$w] = 0;
                $words[$w]++;
            }
        }
    }
}

if (!empty($words)) {
    arsort($words);
    $_SESSION['top_word'] = array_key_first($words);
} else {
    $_SESSION['top_word'] = "—";
}



$is_admin = (($_SESSION['user_role'] ?? '') === 'admin');

$queue = [];
$queue_limit = 10;

if ($is_admin) {
    $sqlQueue = "SELECT id, user_id, filename, suspicious_keywords, result, risk_level, created_at
                 FROM analysis_history
                 ORDER BY risk_level DESC, created_at DESC
                 LIMIT ?";
    $stmtQ = $conn->prepare($sqlQueue);
    $stmtQ->bind_param("i", $queue_limit);
} else {
    $sqlQueue = "SELECT id, user_id, filename, suspicious_keywords, result, risk_level, created_at
                 FROM analysis_history
                 WHERE user_id = ?
                 ORDER BY risk_level DESC, created_at DESC
                 LIMIT ?";
    $stmtQ = $conn->prepare($sqlQueue);
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stmtQ->bind_param("ii", $uid, $queue_limit);
}

if ($stmtQ && $stmtQ->execute()) {
    $resQ = $stmtQ->get_result();
    while ($row = $resQ->fetch_assoc()) {
        $kwArr = json_decode($row['suspicious_keywords'] ?? '[]', true);
        if (!is_array($kwArr)) $kwArr = [];
        $row['kwArr'] = $kwArr;

        $cat = $row['result'] ?? 'Normal';
        $risk = (float)($row['risk_level'] ?? 0);


        if (!in_array($cat, ['High Priority','Needs Review','Normal','High Risk','Suspicious','Low Risk','Safe'])) {
            if ($risk >= 75) $cat = 'High Priority';
            elseif ($risk >= 40) $cat = 'Needs Review';
            else $cat = 'Normal';
        }

        $row['category_fixed'] = $cat;

        if ($risk >= 75) {
            $row['badge_class'] = "bg-red-500/20 text-red-300 border border-red-500/30";
            $row['badge_icon']  = "🚨";
        } elseif ($risk >= 40) {
            $row['badge_class'] = "bg-yellow-500/20 text-yellow-300 border border-yellow-500/30";
            $row['badge_icon']  = "⚠️";
        } else {
            $row['badge_class'] = "bg-emerald-500/20 text-emerald-300 border border-emerald-500/30";
            $row['badge_icon']  = "✅";
        }

        $queue[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $lang['dashboard_title'] ?> - Voice Shield</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet" />

  <style>
    :root{
      --accent-1: #7c3aed;
      --accent-2: #2563eb;
      --glass: rgba(255,255,255,0.04);
    }

    html,body{height:100%;}
    body {
      font-family: 'Poppins', sans-serif;
      margin:0;
      min-height:100%;
      background: radial-gradient(1200px 600px at 10% 20%, rgba(124,58,237,0.08), transparent 6%),
                  radial-gradient(1000px 500px at 95% 80%, rgba(37,99,235,0.07), transparent 6%),
                  linear-gradient(180deg,#060615 0%, #0b1020 35%, #1a1635 100%);
      color: #e6eef8;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }

    header, .site-header {
      position: sticky;
      top: 0;
      z-index: 60;
      backdrop-filter: blur(8px) saturate(1.1);
      background: linear-gradient(90deg, rgba(2,6,23,0.6), rgba(13,10,30,0.55));
      border-bottom: 1px solid rgba(255,255,255,0.04);
      box-shadow: 0 6px 30px rgba(2,6,23,0.45);
    }

    .glow-circle {
      position: fixed;
      border-radius: 9999px;
      filter: blur(56px);
      opacity: 0.7;
      z-index: -1;
      pointer-events: none;
      mix-blend-mode: screen;
    }
    .glow-1{width:420px; height:420px; left:-80px; top:-100px; background: linear-gradient(90deg,var(--accent-2), #8b5cf6);}
    .glow-2{width:360px; height:360px; right:-60px; bottom:-80px; background: linear-gradient(120deg,#8b5cf6,#ec4899);}

    .glass-card {
      background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      border: 1px solid rgba(255,255,255,0.04);
      border-radius: 16px;
      padding: 1.25rem;
      box-shadow: 0 8px 40px rgba(2,6,23,0.6);
      backdrop-filter: blur(8px) saturate(1.1);
      transition: transform .28s cubic-bezier(.2,.9,.2,1), box-shadow .28s;
    }
    .glass-card:hover{ transform: translateY(-6px); box-shadow: 0 18px 60px rgba(124,58,237,0.18); }

    .cta {
      background: linear-gradient(90deg,var(--accent-2),var(--accent-1));
      box-shadow: 0 8px 30px rgba(124,58,237,0.28);
      border-radius: 12px;
      padding: .6rem 1.05rem;
      font-weight: 700;
    }

    .enter-up { transform: translateY(14px); opacity: 0; animation: enterUp .6s forwards cubic-bezier(.2,.9,.2,1); }
    @keyframes enterUp { to { transform: translateY(0); opacity: 1; } }

    .badge {
      background: linear-gradient(90deg,#10b981,#34d399);
      color: #04202a;
      font-weight:700;
      padding: .25rem .6rem;
      border-radius: 999px;
      font-size: .82rem;
      display:inline-block;
    }

    .muted { color: rgba(230,238,248,0.72); }
    .center-max { max-width: 980px; margin-left:auto; margin-right:auto; }
  </style>
</head>
<body>

  <div class="glow-circle glow-1"></div>
  <div class="glow-circle glow-2"></div>

  <?php include 'includes/header.php'; ?>

  <div class="h-24"></div>

  <main class="px-6 pb-16">
    <div class="center-max">

      <?php if (isset($_SESSION['new_analysis_done'])): ?>
        <div class="glass-card mb-6 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="badge">✅ New</div>
            <div>
              <div class="font-semibold"><?= $lang['new_analysis_alert'] ?? 'A new analysis is available!' ?></div>
              <div class="muted text-sm">Check it now in your results.</div>
            </div>
          </div>
          <div><button onclick="this.parentElement.parentElement.style.display='none'" class="text-slate-200/70 hover:opacity-90">✕</button></div>
        </div>
        <?php unset($_SESSION['new_analysis_done']); ?>
      <?php endif; ?>

      <section class="glass-card grid grid-cols-1 md:grid-cols-3 gap-6 items-center mb-8 enter-up">

        <div class="md:col-span-1 text-center md:text-left">
          <div class="mx-auto md:mx-0 w-28 h-28 rounded-full overflow-hidden shadow-lg bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
            <svg width="72" height="72" viewBox="0 0 100 100"><circle cx="50" cy="50" r="46" fill="rgba(255,255,255,0.06)"/><path d="M30 60c6-6 18-6 24 0" stroke="white" stroke-width="3" stroke-linecap="round" fill="none" opacity="0.9"/><circle cx="38" cy="44" r="4" fill="white"/><circle cx="62" cy="44" r="4" fill="white"/></svg>
          </div>

          <h1 class="mt-4 text-2xl font-extrabold leading-tight">
            Welcome, <?= htmlspecialchars($_SESSION['username']); ?>!
          </h1>
          <p class="muted mt-2 max-w-sm">
            <?= $lang['dashboard_message'] ?>
          </p>

          <div class="mt-4 flex flex-wrap gap-3 justify-center md:justify-start">
            <a href="upload.php" class="cta text-white inline-flex items-center gap-2">⬆ Upload Audio</a>
            <a href="result.php" class="px-4 py-2 rounded-lg bg-white/6 text-white font-semibold hover:scale-[1.02] transition">View Results</a>

            <a href="history.php"
               class="px-4 py-2 rounded-lg bg-white/10 text-white font-semibold hover:scale-[1.02] transition">
               📁 My History
            </a>

            <a href="live_listen.php"
               class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition inline-flex items-center gap-2">
               🎧 Live AI Listening
            </a>

          </div>
        </div>

        <div class="md:col-span-1 flex flex-col gap-4">
          <div class="bg-white/5 rounded-lg p-4">
            <div class="text-sm muted">Analyses</div>
            <div class="text-2xl font-bold mt-1"><?= htmlspecialchars($_SESSION['total_analyses'] ?? '—') ?></div>
            <div class="text-xs muted mt-1">Upload and check your analyses anytime</div>
          </div>

          <div class="bg-white/5 rounded-lg p-4">
            <div class="text-sm muted">Security Level</div>
            <div class="mt-2">
              <div class="w-full h-3 bg-white/10 rounded-full overflow-hidden">
                <div style="width:24%; background:linear-gradient(90deg,#f97316,#f43f5e);" class="h-full rounded-full"></div>
              </div>
              <div class="text-xs muted mt-2">Phishing risk preview</div>
            </div>
          </div>
        </div>

        <div class="md:col-span-1 flex flex-col gap-3 items-center md:items-end">
          <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <a href="admin_dashboard.php" class="px-4 py-3 rounded-lg bg-white/10 w-full text-center font-semibold hover:scale-[1.02] transition">Admin Panel</a>
          <?php else: ?>
            <a href="my_analysis.php" class="px-4 py-3 rounded-lg bg-white/10 w-full text-center font-semibold hover:scale-[1.02] transition">My Analyses</a>
          <?php endif; ?>
          <a href="faqs.php" class="px-4 py-3 rounded-lg bg-white/10 w-full text-center font-semibold hover:scale-[1.02] transition">FAQs</a>
          <a href="logout.php" class="px-4 py-3 rounded-lg bg-red-600 text-white w-full text-center font-semibold hover:opacity-95 transition">Logout</a>
        </div>
      </section>

      <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <div class="glass-card">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm muted">Total Users</div>
              <div class="text-2xl font-bold mt-1"><?= htmlspecialchars($_SESSION['total_users'] ?? '—') ?></div>
            </div>
            <div class="text-xs badge">Members</div>
          </div>
        </div>

        <div class="glass-card">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm muted">Total Analyses</div>
              <div class="text-2xl font-bold mt-1"><?= htmlspecialchars($_SESSION['total_analyses'] ?? '—') ?></div>
            </div>
            <div class="text-xs badge">Reports</div>
          </div>
        </div>

        <div class="glass-card">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm muted">Top Suspicious Word</div>
              <div class="text-2xl font-bold mt-1"><?= htmlspecialchars($_SESSION['top_word'] ?? '—') ?></div>
            </div>
            <div class="text-xs badge">Alert</div>
          </div>
        </div>

      </section>


      <section class="glass-card mb-10 enter-up">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h3 class="text-xl font-bold">🚦 Smart Incident Queue</h3>
            <p class="muted mt-1 text-sm">
              Sorted automatically by Risk Level (highest first). <?= $is_admin ? "Admin view: all users." : "Your latest analyses." ?>
            </p>
          </div>
          <div class="flex gap-2">
            <a href="history.php" class="px-4 py-2 rounded-lg bg-white/10 text-white font-semibold hover:scale-[1.02] transition">Open History</a>
            <a href="result.php" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">View Results</a>
          </div>
        </div>

        <div class="mt-5 overflow-x-auto">
          <table class="w-full text-sm border border-white/10 rounded-xl overflow-hidden">
            <thead class="bg-white/5 text-slate-200">
              <tr>
                <th class="p-3 text-left">Priority</th>
                <th class="p-3 text-left">File</th>
                <th class="p-3 text-left">Keywords</th>
                <th class="p-3 text-left">Risk</th>
                <th class="p-3 text-left">Time</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
              <?php if (!empty($queue)): ?>
                <?php foreach ($queue as $item): ?>
                  <?php
                    $kwShort = "—";
                    if (!empty($item['kwArr'])) {
                        $kwShort = implode(", ", array_slice($item['kwArr'], 0, 6));
                        if (count($item['kwArr']) > 6) $kwShort .= " …";
                    }
                    $riskVal = (float)($item['risk_level'] ?? 0);
                    $file = $item['filename'] ?? '—';
                    $time = $item['created_at'] ?? '';
                    $cat  = $item['category_fixed'] ?? 'Normal';
                    $badgeClass = $item['badge_class'] ?? "bg-white/10 text-white border border-white/10";
                    $badgeIcon  = $item['badge_icon'] ?? "ℹ️";
                  ?>
                  <tr class="hover:bg-white/5 transition">
                    <td class="p-3">
                      <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full <?= htmlspecialchars($badgeClass) ?>">
                        <span><?= $badgeIcon ?></span>
                        <span class="font-semibold"><?= htmlspecialchars($cat) ?></span>
                      </span>
                    </td>
                    <td class="p-3">
                      <div class="font-semibold text-slate-100"><?= htmlspecialchars($file) ?></div>
                      <div class="text-xs muted">ID: <?= (int)$item['id'] ?></div>
                    </td>
                    <td class="p-3">
                      <span class="text-slate-200"><?= htmlspecialchars($kwShort) ?></span>
                    </td>
                    <td class="p-3">
                      <div class="font-bold text-slate-100"><?= htmlspecialchars($riskVal) ?>%</div>
                      <div class="w-32 h-2 bg-white/10 rounded-full overflow-hidden mt-2">
                        <div class="h-2 rounded-full" style="width: <?= min(100, max(0, $riskVal)) ?>%; background: linear-gradient(90deg,#7c3aed,#2563eb);"></div>
                      </div>
                    </td>
                    <td class="p-3">
                      <div class="text-slate-200"><?= htmlspecialchars($time) ?></div>
                      <div class="text-xs muted"><?= $is_admin ? ("User ID: " . (int)$item['user_id']) : "" ?></div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="p-6 text-center text-slate-400">
                    No queue items yet. Upload an audio file or try Live Listening.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-4 text-xs muted">
          Tip: This queue proves “smart sorting” — highest risk incidents appear first automatically.
        </div>
      </section>

      <section class="rounded-2xl border border-white/6 bg-gradient-to-r from-indigo-900/40 to-purple-900/30 p-6 mb-10 backdrop-blur shadow-lg enter-up">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
          <div>
            <h3 class="text-xl font-bold">Quick Program Overview</h3>
            <p class="muted mt-1 max-w-xl">
              A modern AI-based voice verification & phishing detection platform — polished and production-ready look.
            </p>
          </div>
          <div class="flex gap-3">
            <a href="upload.php" class="cta">Start Now</a>
            <a href="result.php" class="px-4 py-2 rounded-lg bg-white/10 text-white font-semibold">View Results</a>
          </div>
        </div>
      </section>

    </div>
  </main>

  <footer class="mt-12 text-center py-6 text-slate-400 text-sm">
    © <?= date('Y') ?> Voice Shield — Secure voice, secure future
  </footer>

</body>
</html>
