<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <title>Live AI Listening | Voice Shield</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />

  <style>
    body{
      font-family:'Poppins',sans-serif;
      background:radial-gradient(circle at 15% 20%, rgba(124,58,237,0.12), transparent 40%),
                 radial-gradient(circle at 85% 80%, rgba(37,99,235,0.12), transparent 40%),
                 #0B0F19;
      color:#e9e9e9;
    }
    .glass{
      background:rgba(255,255,255,0.04);
      border:1px solid rgba(255,255,255,0.08);
      backdrop-filter:blur(12px) saturate(1.2);
      border-radius:18px;
      padding:24px;
    }
    .pulse-dot{
      width:14px;height:14px;border-radius:50%;
      background:#ef4444;animation:pulse 1.2s infinite;
    }
    @keyframes pulse{
      0%{transform:scale(0.85);opacity:0.6;}
      50%{transform:scale(1);opacity:1;}
      100%{transform:scale(0.85);opacity:0.6;}
    }
    .neon-text{
      color:#A78BFA;
      text-shadow:0 0 8px rgba(167,139,250,0.6);
    }
    .risk-box{border-left:4px solid #6366f1;padding-left:12px;}
    .flash-alert{animation:flash 0.4s ease-in-out 2;}
    @keyframes flash{
      0%{background-color:rgba(255,0,0,0.25);}
      50%{background-color:rgba(255,0,0,0.55);}
      100%{background-color:transparent;}
    }
    .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
  </style>
</head>

<body class="pt-12 px-6">

<audio id="alertSound" src="alert.mp3" preload="auto"></audio>

<div class="max-w-4xl mx-auto">
  <h1 class="text-3xl font-semibold neon-text mb-6 flex items-center gap-3">
    <span>🎧 Live AI Call Analysis</span>
    <div class="pulse-dot"></div>
    <span class="text-sm text-yellow-400">(Prototype)</span>
  </h1>

  <div class="glass mb-8 text-center py-8">
    <button id="startButton"
      class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg transition">
      🎤 Start Live Listening
    </button>

    <button id="stopButton"
      class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl shadow-lg transition hidden mt-4">
      ⛔ Stop Live Listening
    </button>

    <div class="mt-5 flex flex-col items-center gap-2">
      <label class="flex items-center gap-2 text-sm text-gray-200">
        <input id="consent" type="checkbox" class="scale-110">
        I agree to recording/saving audio chunks for analysis
      </label>
      <p class="text-xs text-gray-400 max-w-xl">
        If not checked, Voice Shield will analyze in real-time and delete the audio chunk immediately (no saving).
      </p>
    </div>

    <p class="mt-4 text-gray-400 text-sm">
      Voice Shield will analyze your call in real-time using AI (Whisper).
    </p>

    <p id="codecInfo" class="mt-2 text-xs text-gray-500"></p>
    <p id="netInfo" class="mt-2 text-xs text-gray-500 mono"></p>

    <p id="saveStatus" class="mt-3 text-xs text-emerald-300"></p>
  </div>

  <div class="glass mb-8">
    <h2 class="text-xl font-semibold mb-4">Live AI Output</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="glass p-4 risk-box">
        <div class="text-sm text-gray-300">Risk Level</div>
        <div id="riskLevel" class="text-3xl font-bold mt-2 text-red-400">— %</div>
      </div>

      <div class="glass p-4">
        <div class="text-sm text-gray-300">Suspicious Keywords</div>
        <div id="liveKeywords" class="text-lg mt-2 text-yellow-300">—</div>
      </div>

      <div class="glass p-4">
        <div class="text-sm text-gray-300">Detection Category</div>
        <div id="liveCategory" class="text-lg mt-2 text-blue-300">—</div>
      </div>
    </div>

    <div class="mt-6">
      <div class="text-sm text-gray-300 mb-2">Live Transcript</div>
      <div id="liveTranscript" class="glass p-4 h-40 overflow-y-auto text-sm text-gray-200">
        Waiting for audio…
      </div>
    </div>

    <div class="mt-6 glass p-4">
      <div class="text-sm text-gray-300 mb-2">AI Safety Advice</div>
      <div id="liveAdvice" class="text-indigo-300">Waiting...</div>
    </div>

    <div class="mt-8 glass p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">Incoming Alerts Queue</h3>
        <button id="clearQueue"
          class="text-xs px-3 py-1 bg-gray-800 rounded-lg hover:bg-gray-700">
          Clear
        </button>
      </div>
      <div id="alertsQueue" class="space-y-2 text-sm text-gray-200"></div>
      <p class="text-xs text-gray-400 mt-3">
        Alerts are added automatically when risk ≥ 40% (triage).
      </p>
    </div>
  </div>

  <a href="dashboard.php"
     class="px-6 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg shadow-md transition inline-block">
     ← Back to Dashboard
  </a>
</div>

<script>
let mediaRecorder;
let isRecording = false;
let activeStream = null;
let chunkTimer = null;

let incidentSavedAuto = false;

let lastPayload = null;

document.getElementById("clearQueue").addEventListener("click", () => {
  document.getElementById("alertsQueue").innerHTML = "";
});

function pickMimeType() {
  const candidates = [
    "audio/webm;codecs=opus",
    "audio/webm",
    "audio/mp4",
    "audio/aac"
  ];
  for (const t of candidates) {
    if (window.MediaRecorder && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t)) {
      return t;
    }
  }
  return "";
}

function apiUrl(file) {
  return new URL(file, window.location.href).toString();
}

async function saveIncident(payload, modeLabel="Manual") {
  const statusEl = document.getElementById("saveStatus");
  statusEl.textContent = "Saving incident to queue...";

  const fd = new FormData();
  fd.append("text", payload.text || "");
  fd.append("risk", String(payload.risk ?? 0));
  fd.append("category", payload.category || "Normal");
  fd.append("keywords", payload.keywords || "");

  try {
    const r = await fetch(apiUrl("live_save_incident.php"), { method: "POST", body: fd });
    const raw = await r.text();

    let d;
    try { d = JSON.parse(raw); }
    catch(e){ d = { ok:false, msg:"Non-JSON from live_save_incident.php", raw }; }

    if (d.ok) {
      statusEl.textContent = `✅ Saved to Queue (${modeLabel}) • Incident ID: ${d.id}`;
      return { ok:true, id:d.id };
    } else {
      statusEl.textContent = `❌ Save failed (${modeLabel}): ${d.msg || "Unknown error"}`;
      return { ok:false };
    }
  } catch (e) {
    statusEl.textContent = `❌ Save failed (${modeLabel}): Fetch error`;
    return { ok:false };
  }
}

document.getElementById("startButton").addEventListener("click", async () => {
  if (isRecording) return;
  isRecording = true;

  incidentSavedAuto = false;
  lastPayload = null;
  document.getElementById("saveStatus").textContent = "";

  document.getElementById("startButton").classList.add("hidden");
  document.getElementById("stopButton").classList.remove("hidden");

  const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
  activeStream = stream;

  const mimeType = pickMimeType();
  const infoEl = document.getElementById("codecInfo");
  infoEl.textContent = mimeType ? ("Recording format: " + mimeType) : "Recording format: default";

  const options = mimeType ? { mimeType } : {};
  mediaRecorder = new MediaRecorder(stream, options);

  mediaRecorder.start();

  chunkTimer = setInterval(() => {
    try {
      if (mediaRecorder && mediaRecorder.state === "recording") {
        mediaRecorder.requestData();
      }
    } catch (e) {}
  }, 3000);

  mediaRecorder.ondataavailable = (e) => {
    if (!isRecording) return;
    if (!e.data || e.data.size === 0) return;
    sendChunk(e.data, mimeType);
  };

  mediaRecorder.onerror = (e) => {
    console.log("MediaRecorder error:", e);
  };
});

document.getElementById("stopButton").addEventListener("click", () => {
  if (!isRecording) return;
  isRecording = false;

  if (chunkTimer) {
    clearInterval(chunkTimer);
    chunkTimer = null;
  }

  try { mediaRecorder.stop(); } catch(e){}

  try {
    if (activeStream) activeStream.getTracks().forEach(t => t.stop());
  } catch(e){}

  document.getElementById("stopButton").classList.add("hidden");
  document.getElementById("startButton").classList.remove("hidden");

  document.getElementById("liveTranscript").innerHTML +=
    "<div class='text-red-400 mt-3'>[Listening Stopped]</div>";
});

async function sendChunk(blob, mimeType) {
  let fd = new FormData();

  let filename = "chunk.webm";
  if (mimeType && mimeType.includes("mp4")) filename = "chunk.mp4";

  fd.append("audio_chunk", blob, filename);

  const consent = document.getElementById("consent").checked ? "1" : "0";
  fd.append("consent", consent);

  try {
    const url = apiUrl("live_process.php");
    const r = await fetch(url, { method: "POST", body: fd });

    const raw = await r.text();
    document.getElementById("netInfo").textContent = "live_process.php status: " + r.status;

    let d;
    try {
      d = JSON.parse(raw);
    } catch (e) {
      console.log("Server returned non-JSON:", raw);
      document.getElementById("liveCategory").innerHTML = "Error";
      document.getElementById("liveAdvice").innerHTML = "Server returned non-JSON. Check Console/Network.";
      return;
    }

    updateUI(d);

  } catch (e) {
    console.log("Fetch Error:", e);
    document.getElementById("liveCategory").innerHTML = "Error";
    document.getElementById("liveAdvice").innerHTML = "Fetch failed. Check Console/Network.";
  }
}

function updateUI(data) {
  const risk = (data.risk ?? 0);

  document.getElementById("riskLevel").innerHTML = risk + "%";
  document.getElementById("liveKeywords").innerHTML = data.keywords || "—";
  document.getElementById("liveCategory").innerHTML = data.category || "—";

  if (data.category === "Listening") {
    document.getElementById("liveAdvice").innerHTML = "🎧 جارٍ الاستماع وتحليل الصوت...";
  } else {
    document.getElementById("liveAdvice").innerHTML = data.actions || "—";
  }

  if (risk >= 75) {
    document.getElementById("alertSound").play().catch(()=>{});
    let rl = document.getElementById("riskLevel");
    rl.classList.add("flash-alert");
    setTimeout(() => rl.classList.remove("flash-alert"), 600);
  }

  if (data.text && data.text.trim() !== "") {
    lastPayload = {
      text: data.text,
      risk: risk,
      category: data.category || "Normal",
      keywords: data.keywords || ""
    };

    let box = document.getElementById("liveTranscript");
    if (box.innerText.includes("Waiting for audio")) box.innerHTML = ""; 
    box.innerHTML += "<div>" + escapeHtml(data.text) + "</div>";
    box.scrollTop = box.scrollHeight;
  }

  if (risk >= 40 && data.text && data.text.trim() !== "") {
    const q = document.getElementById("alertsQueue");
    const now = new Date().toLocaleTimeString();

    const item = document.createElement("div");
    item.className = "glass p-3 flex items-start justify-between gap-3";

    item.dataset.text = data.text;
    item.dataset.risk = String(risk);
    item.dataset.category = data.category || "Normal";
    item.dataset.keywords = data.keywords || "";

    item.innerHTML = `
      <div>
        <div class="font-semibold">${escapeHtml(data.category || "Alert")} • ${risk}%</div>
        <div class="text-xs text-gray-400">${now}</div>
        <div class="mt-1">${escapeHtml(data.text)}</div>
        <div class="mt-2 text-yellow-300 text-xs">${escapeHtml(data.keywords || "")}</div>
        <div class="mt-2 text-emerald-300 text-xs hidden" data-saved-badge>✅ Saved to Queue</div>
      </div>
      <button class="text-xs px-3 py-1 bg-indigo-600 rounded-lg hover:bg-indigo-700" data-create-incident>
        Create Incident
      </button>
    `;
    q.prepend(item);

    const btn = item.querySelector("[data-create-incident]");
    btn.addEventListener("click", async () => {
      btn.disabled = true;
      btn.textContent = "Saving...";
      const payload = {
        text: item.dataset.text || "",
        risk: parseFloat(item.dataset.risk || "0"),
        category: item.dataset.category || "Normal",
        keywords: item.dataset.keywords || ""
      };
      const res = await saveIncident(payload, "Manual");
      if (res.ok) {
        const badge = item.querySelector("[data-saved-badge]");
        if (badge) badge.classList.remove("hidden");
        btn.textContent = "Saved";
        btn.classList.remove("bg-indigo-600","hover:bg-indigo-700");
        btn.classList.add("bg-emerald-700");
      } else {
        btn.disabled = false;
        btn.textContent = "Create Incident";
      }
    });

    if (!incidentSavedAuto) {
      incidentSavedAuto = true;
      saveIncident({
        text: data.text,
        risk: risk,
        category: data.category || "Needs Review",
        keywords: data.keywords || ""
      }, "Auto-Triage").then(res => {
        if (res.ok) {
          const badge = item.querySelector("[data-saved-badge]");
          if (badge) badge.classList.remove("hidden");
        } else {
          incidentSavedAuto = false;
        }
      });
    }
  }
}

function escapeHtml(str){
  return String(str)
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}
</script>

</body>
</html>
