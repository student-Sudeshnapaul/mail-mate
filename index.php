<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI Email Composer</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --void: #05070d;
    --panel: rgba(18, 24, 41, 0.72);
    --panel-solid: #0f1524;
    --line: rgba(120, 170, 255, 0.14);
    --cyan: #3ee6ff;
    --violet: #9c6bff;
    --text: #e8ecf6;
    --muted: #7c8aa8;
    --success: #3ddc97;
    --error: #ff5c7a;
    --font-display: 'Sora', sans-serif;
    --font-body: 'Inter', sans-serif;
    --font-mono: 'JetBrains Mono', monospace;
  }

  * { box-sizing: border-box; }

  html { background: var(--void); }

  body {
    font-family: var(--font-body);
    background: var(--void);
    color: var(--text);
    margin: 0;
    padding: 0;
    min-height: 100vh;
    position: relative;
    overflow-x: hidden;
  }

  /* Ambient glow blobs drifting behind everything */
  body::before, body::after {
    content: '';
    position: fixed;
    width: 640px;
    height: 640px;
    border-radius: 50%;
    filter: blur(120px);
    z-index: 0;
    opacity: 0.16;
    pointer-events: none;
  }
  body::before {
    background: var(--cyan);
    top: -200px;
    left: -160px;
    animation: drift1 22s ease-in-out infinite alternate;
  }
  body::after {
    background: var(--violet);
    bottom: -220px;
    right: -180px;
    animation: drift2 26s ease-in-out infinite alternate;
  }
  @keyframes drift1 {
    to { transform: translate(80px, 60px) scale(1.1); }
  }
  @keyframes drift2 {
    to { transform: translate(-60px, -40px) scale(1.15); }
  }
  @media (prefers-reduced-motion: reduce) {
    body::before, body::after { animation: none; }
  }

  /* --- Banner --- */
  .banner {
    width: 100%;
    height: 260px;
    overflow: hidden;
    background: radial-gradient(ellipse at 30% 20%, rgba(62,230,255,0.10), transparent 55%),
                radial-gradient(ellipse at 75% 70%, rgba(156,107,255,0.12), transparent 55%),
                var(--void);
    position: relative;
  }
  .banner svg { position: absolute; inset: 0; width: 100%; height: 100%; }
  .banner .node {
    fill: #cfe6ff;
    animation: nodepulse 3.4s ease-in-out infinite;
  }
  .banner .link {
    stroke: rgba(120, 170, 255, 0.28);
    stroke-width: 1;
    fill: none;
  }
  @keyframes nodepulse {
    0%, 100% { opacity: 0.55; }
    50% { opacity: 1; }
  }
  @media (prefers-reduced-motion: reduce) {
    .banner .node { animation: none; opacity: 0.8; }
  }

  .signal-line {
    position: absolute;
    left: 0; right: 0;
    top: 62%;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--cyan), var(--violet), transparent);
    background-size: 200% 100%;
    animation: scan 4s linear infinite;
    opacity: 0.85;
    z-index: 3;
  }
  .envelope-glyph {
    position: absolute;
    top: 62%;
    left: 0;
    font-size: 20px;
    transform: translate(-50%, -50%);
    filter: drop-shadow(0 0 8px var(--cyan));
    animation: fly 4s linear infinite;
    z-index: 4;
  }
  @keyframes scan {
    to { background-position: -200% 0; }
  }
  @keyframes fly {
    0% { left: -4%; }
    100% { left: 104%; }
  }
  @media (prefers-reduced-motion: reduce) {
    .signal-line, .envelope-glyph { animation: none; opacity: 0.5; }
  }

  .banner::after {
    /* fade hero into the void background so it has no hard edge */
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(5,7,13,0) 0%, rgba(5,7,13,0.5) 70%, var(--void) 100%);
    z-index: 2;
  }
  .banner-caption {
    position: absolute;
    left: 50%;
    bottom: 14px;
    transform: translateX(-50%);
    z-index: 4;
    font-family: var(--font-mono);
    font-size: 11px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--cyan);
    opacity: 0.75;
    white-space: nowrap;
  }

  .page-body { padding: 0 16px 48px; position: relative; z-index: 1; }
  .container { max-width: 720px; margin: 20px auto 0; position: relative; }

  h1 {
    font-family: var(--font-display);
    font-size: 26px;
    font-weight: 700;
    margin: 0 0 6px;
    letter-spacing: -0.01em;
    background: linear-gradient(90deg, #ffffff, var(--cyan) 130%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  .subtitle { color: var(--muted); margin-bottom: 24px; font-size: 14px; }

  .card {
    background: var(--panel);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.04);
    position: relative;
    overflow: hidden;
  }
  /* Signature: transmitting sweep across the top edge while busy */
  .card::before {
    content: '';
    position: absolute;
    top: 0; left: -100%;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--cyan), var(--violet), transparent);
    transition: opacity 0.2s;
    opacity: 0;
  }
  .card.is-transmitting::before {
    opacity: 1;
    animation: transmit 1.1s linear infinite;
  }
  @keyframes transmit {
    to { left: 100%; }
  }

  label {
    display: block;
    font-family: var(--font-display);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 8px;
    margin-top: 18px;
  }
  label:first-child { margin-top: 0; }

  input, select, textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--line);
    border-radius: 8px;
    font-size: 14px;
    font-family: var(--font-body);
    background: rgba(255,255,255,0.03);
    color: var(--text);
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
  }
  input::placeholder, textarea::placeholder { color: #4d5975; }
  input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--cyan);
    background: rgba(62, 230, 255, 0.04);
    box-shadow: 0 0 0 3px rgba(62, 230, 255, 0.12), 0 0 20px rgba(62, 230, 255, 0.1);
  }
  select { color-scheme: dark; }
  select option { color: #14181f; background: #fff; }
  input[type="date"] { font-family: var(--font-mono); }

  textarea { resize: vertical; min-height: 90px; }
  .row { display: flex; gap: 12px; }
  .row + .row { margin-top: 2px; }
  .row label { margin-top: 20px; }
  .row > div { flex: 1; }

  button {
    background: linear-gradient(135deg, var(--cyan), var(--violet));
    color: #06090f;
    border: none;
    padding: 12px 22px;
    border-radius: 8px;
    font-size: 14px;
    font-family: var(--font-display);
    font-weight: 700;
    letter-spacing: 0.01em;
    cursor: pointer;
    margin-top: 22px;
    box-shadow: 0 0 0 rgba(62,230,255,0);
    transition: box-shadow 0.2s, transform 0.1s, filter 0.15s;
  }
  button:hover:not(:disabled) {
    box-shadow: 0 0 24px rgba(62, 230, 255, 0.45), 0 0 40px rgba(156, 107, 255, 0.25);
    filter: brightness(1.05);
  }
  button:active:not(:disabled) { transform: translateY(1px); }
  button:disabled { background: #2a3244; color: #5b6784; cursor: not-allowed; }
  button.secondary {
    background: transparent;
    color: var(--text);
    border: 1px solid var(--line);
    box-shadow: none;
  }
  button.secondary:hover { border-color: var(--muted); box-shadow: none; filter: none; }

  #previewSection { display: none; }

  .status {
    margin-top: 16px;
    padding: 11px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-family: var(--font-mono);
    display: none;
    border: 1px solid transparent;
  }
  .status.success { background: rgba(61, 220, 151, 0.08); color: var(--success); border-color: rgba(61,220,151,0.3); display: block; }
  .status.error { background: rgba(255, 92, 122, 0.08); color: var(--error); border-color: rgba(255,92,122,0.3); display: block; }
  .status.loading { background: rgba(62, 230, 255, 0.06); color: var(--cyan); border-color: rgba(62,230,255,0.25); display: block; }

  .btn-row { display: flex; gap: 10px; }

  /* --- Mail preview: looks like an actual email, not raw HTML --- */
  .mail-preview {
    background: #f4f5f7;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
  }
  .mail-preview-header {
    background: #ffffff;
    border-bottom: 1px solid #e4e6ea;
    padding: 16px 20px;
  }
  .mail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13.5px;
    color: #202124;
    padding: 4px 0;
  }
  .mail-row + .mail-row { border-top: 1px solid #eef0f3; }
  .mail-label {
    font-family: var(--font-display);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: #8a8f98;
    width: 52px;
    flex-shrink: 0;
  }
  #previewTo { color: #202124; font-family: var(--font-body); }
  .mail-subject-input {
    flex: 1;
    border: none;
    background: transparent;
    font-family: var(--font-body);
    font-size: 15px;
    font-weight: 600;
    color: #17181c;
    padding: 2px 0;
  }
  .mail-subject-input:focus {
    outline: none;
    box-shadow: none;
    background: transparent;
  }
  .mail-body {
    background: #ffffff;
    color: #1a1c20;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 14.5px;
    line-height: 1.65;
    padding: 24px 20px;
    min-height: 220px;
    max-height: 420px;
    overflow-y: auto;
  }
  .mail-body:focus { outline: none; }
  .mail-body p { margin: 0 0 14px; }
  .mail-body p:last-child { margin-bottom: 0; }
  .mail-preview-footer {
    background: #f4f5f7;
    padding: 8px 20px 14px;
    font-family: var(--font-mono);
    font-size: 11px;
    color: #9aa0ac;
  }
</style>
</head>
<body>

<div class="banner">
  <svg viewBox="0 0 1200 260" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <g class="link">
      <line x1="90" y1="60" x2="260" y2="130"/>
      <line x1="260" y1="130" x2="470" y2="70"/>
      <line x1="470" y1="70" x2="640" y2="150"/>
      <line x1="640" y1="150" x2="820" y2="90"/>
      <line x1="820" y1="90" x2="1010" y2="140"/>
      <line x1="1010" y1="140" x2="1150" y2="60"/>
      <line x1="260" y1="130" x2="470" y2="190"/>
      <line x1="640" y1="150" x2="820" y2="200"/>
    </g>
    <circle class="node" cx="90" cy="60" r="4" style="animation-delay:0s"/>
    <circle class="node" cx="260" cy="130" r="4" style="animation-delay:0.3s"/>
    <circle class="node" cx="470" cy="70" r="4" style="animation-delay:0.6s"/>
    <circle class="node" cx="470" cy="190" r="3" style="animation-delay:1.4s"/>
    <circle class="node" cx="640" cy="150" r="4" style="animation-delay:0.9s"/>
    <circle class="node" cx="820" cy="90" r="4" style="animation-delay:1.2s"/>
    <circle class="node" cx="820" cy="200" r="3" style="animation-delay:1.8s"/>
    <circle class="node" cx="1010" cy="140" r="4" style="animation-delay:1.5s"/>
    <circle class="node" cx="1150" cy="60" r="4" style="animation-delay:1.8s"/>
  </svg>
  <div class="signal-line"></div>
  <div class="envelope-glyph">✉</div>
  <div class="banner-caption">// drafting · encoding · transmitting</div>
</div>

<div class="page-body">
<div class="container">
  <h1>✉ AI Email Composer</h1>
  <div class="subtitle">Fill in the details, generate a draft with Gemini, review it, then send.</div>

  <div class="card" id="formSection">
    <label>Receiver's Name (optional)</label>
    <input type="text" id="receiverName" placeholder="e.g. Mr. Sharma">

    <label>Receiver's Email *</label>
    <input type="email" id="receiverEmail" placeholder="e.g. recipient@example.com" required>

    <label>Content / Purpose of Email *</label>
    <textarea id="content" placeholder="e.g. Requesting leave approval for next week due to a family function" required></textarea>

    <div class="row">
      <div>
        <label>Greeting Word</label>
        <select id="greetingWord">
          <option value="Dear">Dear</option>
          <option value="Respected">Respected</option>
        </select>
      </div>
      <div>
        <label>Honorific</label>
        <select id="honorific">
          <option value="Sir">Sir</option>
          <option value="Ma'am">Ma'am</option>
          <option value="Sir/Ma'am">Sir/Ma'am</option>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Time of Day</label>
        <select id="timeOfDay">
          <option value="">None</option>
          <option value="morning">Morning</option>
          <option value="afternoon">Afternoon</option>
          <option value="evening">Evening</option>
        </select>
      </div>
      <div>
        <label>Type of Email</label>
        <select id="type">
          <option value="formal">Formal</option>
          <option value="official">Official</option>
          <option value="educational">Educational</option>
          <option value="informal">Informal</option>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Date</label>
        <input type="date" id="date">
      </div>
      <div>
        <label>Sender's Name *</label>
        <input type="text" id="senderName" placeholder="e.g. Rohit Verma" required>
      </div>
    </div>

    <button id="generateBtn" onclick="generateDraft()">Generate Draft →</button>
    <div id="generateStatus" class="status"></div>
  </div>

  <div class="card" id="previewSection">
    <div class="mail-preview">
      <div class="mail-preview-header">
        <div class="mail-row">
          <span class="mail-label">To</span>
          <span id="previewTo"></span>
        </div>
        <div class="mail-row">
          <span class="mail-label">Subject</span>
          <input type="text" id="editSubject" class="mail-subject-input">
        </div>
      </div>
      <div class="mail-body" id="editBody" contenteditable="true"></div>
      <div class="mail-preview-footer">// click the body or subject above to edit before sending</div>
    </div>

    <div class="btn-row">
      <button onclick="sendEmail()" id="sendBtn">Send Email ✅</button>
      <button class="secondary" onclick="backToForm()">← Edit Details</button>
    </div>
    <div id="sendStatus" class="status"></div>
  </div>
</div>
</div>

<script>
document.getElementById('date').valueAsDate = new Date();

async function generateDraft() {
  const receiverEmail = document.getElementById('receiverEmail').value.trim();
  const content = document.getElementById('content').value.trim();
  const senderName = document.getElementById('senderName').value.trim();

  if (!receiverEmail || !content || !senderName) {
    showStatus('generateStatus', 'error', 'Please fill in all required (*) fields.');
    return;
  }

  const payload = {
    receiverName: document.getElementById('receiverName').value.trim(),
    receiverEmail,
    content,
    honorific: document.getElementById('honorific').value,
    greetingWord: document.getElementById('greetingWord').value,
    timeOfDay: document.getElementById('timeOfDay').value,
    type: document.getElementById('type').value,
    senderName,
    date: document.getElementById('date').value,
  };

  const btn = document.getElementById('generateBtn');
  const card = document.getElementById('formSection');
  btn.disabled = true;
  card.classList.add('is-transmitting');
  showStatus('generateStatus', 'loading', '> contacting gemini... generating draft');

  try {
    const res = await fetch('generate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (!res.ok || data.error) {
      showStatus('generateStatus', 'error', 'Error: ' + (data.error || 'Unknown error'));
      btn.disabled = false;
      card.classList.remove('is-transmitting');
      return;
    }

    // Populate preview
    document.getElementById('editSubject').value = data.subject;
    document.getElementById('editBody').innerHTML = data.body;
    document.getElementById('previewTo').textContent = data.receiverName
      ? `${data.receiverName} <${data.receiverEmail}>`
      : data.receiverEmail;
    window.__pendingSend = {
      receiverEmail: data.receiverEmail,
      receiverName: data.receiverName
    };

    document.getElementById('formSection').style.display = 'none';
    document.getElementById('previewSection').style.display = 'block';
    document.getElementById('generateStatus').style.display = 'none';
  } catch (err) {
    showStatus('generateStatus', 'error', 'Request failed: ' + err.message);
  }
  card.classList.remove('is-transmitting');
  btn.disabled = false;
}

async function sendEmail() {
  const subject = document.getElementById('editSubject').value.trim();
  const bodyEl = document.getElementById('editBody');
  const body = bodyEl.innerHTML.trim();
  const btn = document.getElementById('sendBtn');
  const card = document.getElementById('previewSection');

  if (!subject || !bodyEl.textContent.trim()) {
    showStatus('sendStatus', 'error', 'Subject and body cannot be empty.');
    return;
  }

  btn.disabled = true;
  card.classList.add('is-transmitting');
  showStatus('sendStatus', 'loading', '> handshaking with smtp.gmail.com...');

  try {
    const res = await fetch('send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        receiverEmail: window.__pendingSend.receiverEmail,
        receiverName: window.__pendingSend.receiverName,
        subject,
        body
      })
    });
    const data = await res.json();

    if (data.success) {
      showStatus('sendStatus', 'success', '✓ ' + data.message);
      btn.disabled = true;
    } else {
      showStatus('sendStatus', 'error', '✗ ' + data.message);
      btn.disabled = false;
    }
  } catch (err) {
    showStatus('sendStatus', 'error', 'Request failed: ' + err.message);
    btn.disabled = false;
  }
  card.classList.remove('is-transmitting');
}

function backToForm() {
  document.getElementById('previewSection').style.display = 'none';
  document.getElementById('formSection').style.display = 'block';
  document.getElementById('sendStatus').style.display = 'none';
  document.getElementById('sendBtn').disabled = false;
}

function showStatus(elId, type, msg) {
  const el = document.getElementById(elId);
  el.className = 'status ' + type;
  el.textContent = msg;
}
</script>
</body>
</html>