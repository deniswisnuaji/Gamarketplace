document.addEventListener("DOMContentLoaded", () => {
  /* ============ SCAM WATCH ============ */
  // Reports are sent by students (Store). Only reports verified by an admin are shown to everyone.
  const TYPE_LABEL_SW = { payment: "Fake Payment Proof", noshow: "No Show / Ghosting", phishing: "Phishing Link", "fake-item": "Fake / Wrong Item", impersonation: "Impersonation", deposit: "Deposit (DP) Scam", other: "Other" };
  const TYPE_ICON = { payment: "receipt", noshow: "user-x", phishing: "link-2-off", "fake-item": "package-x", impersonation: "venetian-mask", deposit: "wallet", other: "triangle-alert" };
  // General safety advice per scam type (guidance text, not report data)
  const TYPE_TIPS = {
    payment: { flags: ["Only shows a transfer screenshot", "Rushes you to hand over the item"], todo: ["Check the money in your own banking app", "Prefer cash for small items"] },
    noshow: { flags: ["Asks you to reject other buyers", "Keeps changing the meet-up time"], todo: ["Don't hold items without a commitment", "Meet at a public tikum"] },
    phishing: { flags: ["Shortened or misspelled links", "Asks for your password or OTP"], todo: ["Never log in through chat links", "Change your password if you clicked"] },
    "fake-item": { flags: ["Price far below normal", "Won't let you test the item"], todo: ["Test the item at the meet-up", "Walk away if they refuse"] },
    impersonation: { flags: ["Pretends to be an admin", "Asks for OTP codes"], todo: ["Admins never ask for OTP", "Report and block the account"] },
    deposit: { flags: ["Asks for a deposit before you see the item", "Refuses video call or visit"], todo: ["See the item before paying", "Use COD on campus"] },
    other: { flags: ["Pressure to decide quickly", "Moves the chat off GaMarketplace"], todo: ["Take your time", "Keep chats in the app"] },
  };
  const SEV_RANK = { high: 3, medium: 2, low: 1 };
  const MAX_EVIDENCE = 3;
  const view = { search: "", type: "", severity: "", channel: "", tab: "all", sort: "newest" };
  const expanded = new Set();
  const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);
  const isMine = (r) => { const u = me(); return u && r.reporterId === u.id; };
  const visible = () => Store.reports().filter((r) => r.status === "verified" || isMine(r));

  function passes(a) {
    const text = `${a.title} ${a.desc} ${TYPE_LABEL_SW[a.type]}`.toLowerCase();
    if (view.search && !text.includes(view.search)) return false;
    if (view.type && a.type !== view.type) return false;
    if (view.severity && a.risk !== view.severity) return false;
    if (view.channel && a.channel !== view.channel) return false;
    return true;
  }
  function inTab(a, tab) {
    if (tab === "verified") return a.status === "verified";
    if (tab === "review") return a.status === "review" && isMine(a);
    if (tab === "mine") return isMine(a);
    return a.status === "verified" || (isMine(a) && a.status !== "dismissed");
  }
  function sortList(list) {
    const key = { newest: (a) => -(a.verifiedAt || a.createdAt), seen: (a) => -a.seenBy.length, severity: (a) => -SEV_RANK[a.risk] * 1e13 - (a.verifiedAt || a.createdAt) }[view.sort];
    return list.sort((x, y) => key(x) - key(y));
  }

  function alertHTML(a) {
    const open = expanded.has(a.id);
    const u = me();
    const seen = u && a.seenBy.includes(u.id);
    const tips = TYPE_TIPS[a.type] || TYPE_TIPS.other;
    const statusTag = a.status === "verified" ? '<span class="chip-tag chip-tag--verified"><i data-lucide="badge-check"></i> Verified</span>'
      : a.status === "dismissed" ? '<span class="chip-tag">Not published</span>'
      : '<span class="chip-tag chip-tag--review"><i data-lucide="hourglass"></i> Under review</span>';
    return `
      <article class="alert-card alert-card--${a.risk}${isMine(a) ? " is-mine" : ""}" id="alert-${a.id}" data-id="${a.id}">
        <span class="alert-card__icon" aria-hidden="true"><i data-lucide="${TYPE_ICON[a.type]}"></i></span>
        <div class="alert-card__body">
          <div class="alert-card__tags">
            ${a.status === "verified" ? `<span class="chip-tag chip-tag--${a.risk}">${cap(a.risk)} risk</span>` : ""}
            <span class="chip-tag">${TYPE_LABEL_SW[a.type]}</span>
            ${statusTag}
            ${isMine(a) ? '<span class="chip-tag chip-tag--mine">Your report</span>' : ""}
          </div>
          <h3 class="alert-card__title">${esc(a.title)}</h3>
          <p class="alert-card__summary">${esc(a.desc)}</p>
          <div class="alert-card__meta">
            <span><i data-lucide="${a.channel === "online" ? "message-circle-warning" : "map-pin"}"></i>${a.channel === "online" ? "Online / chat" + (a.place !== "Online" ? " · " + esc(a.place) : "") : "Meet-up · " + esc(a.place)}</span>
            <span><i data-lucide="clock"></i>${timeAgo(a.verifiedAt || a.createdAt)}</span>
            ${a.status === "verified" ? `<span><i data-lucide="users"></i>${a.seenBy.length} student${a.seenBy.length === 1 ? "" : "s"} saw this too</span>` : ""}
          </div>
          ${open ? `
          <div class="alert-card__details" id="details-${a.id}">
            <div class="details--flags"><h4><i data-lucide="flag"></i> Red flags</h4><ul>${tips.flags.map((f) => `<li>${esc(f)}</li>`).join("")}</ul></div>
            <div class="details--do"><h4><i data-lucide="shield-check"></i> How to stay safe</h4><ul>${tips.todo.map((f) => `<li>${esc(f)}</li>`).join("")}</ul></div>
          </div>` : ""}
          <div class="alert-card__actions">
            <button type="button" class="small-btn" data-toggle="${a.id}" aria-expanded="${open}" aria-controls="details-${a.id}"><span>${open ? "Hide tips" : "Red flags & tips"}</span> <i data-lucide="chevron-down"></i></button>
            ${a.status === "verified" && !isMine(a) ? `<button type="button" class="small-btn${seen ? " is-on" : ""}" data-seen-btn="${a.id}" aria-pressed="${!!seen}"><i data-lucide="${seen ? "check" : "eye"}"></i><span>${seen ? "You saw this too" : "I saw this too"}</span></button>` : ""}
            ${a.status === "verified" ? `<button type="button" class="small-btn" data-share="${a.id}"><i data-lucide="share-2"></i><span>Share warning</span></button>` : ""}
          </div>
        </div>
      </article>`;
  }

  function render() {
    const base = visible().filter(passes);
    ["all", "verified", "review", "mine"].forEach((t) => ($(`[data-tab-count="${t}"]`).textContent = base.filter((a) => inTab(a, t)).length));
    const list = sortList(base.filter((a) => inTab(a, view.tab)));
    $("#alertList").innerHTML = list.map(alertHTML).join("");
    const empty = $("#emptyState");
    empty.hidden = list.length > 0;
    empty.querySelector("strong").textContent = Store.reports().some((r) => r.status === "verified") ? "No alerts match these filters." : "No verified scam alerts yet.";
    const verified = Store.reports().filter((r) => r.status === "verified");
    $("#statHigh").textContent = verified.filter((r) => r.risk === "high").length;
    $("#statVerified").textContent = verified.length;
    $("#statWeek").textContent = verified.filter((r) => Date.now() - r.verifiedAt < 7 * 86400e3).length;
    $("#statSeen").textContent = verified.reduce((s, r) => s + r.seenBy.length, 0);
    icons();
  }

  $("#alertList").addEventListener("click", async (e) => {
    const toggle = e.target.closest("[data-toggle]");
    if (toggle) { const id = toggle.dataset.toggle; expanded.has(id) ? expanded.delete(id) : expanded.add(id); render(); $(`[data-toggle="${id}"]`)?.focus(); return; }
    const seenBtn = e.target.closest("[data-seen-btn]");
    if (seenBtn) {
      if (!requireLogin("Log in to confirm this alert.")) return;
      const on = Store.toggleSeen(seenBtn.dataset.seenBtn);
      render(); $(`[data-seen-btn="${seenBtn.dataset.seenBtn}"]`)?.focus();
      if (on) toast("Thanks! This helps other students take it seriously.");
      return;
    }
    const share = e.target.closest("[data-share]");
    if (share) {
      const a = Store.getReport(share.dataset.share);
      const tips = TYPE_TIPS[a.type] || TYPE_TIPS.other;
      const text = `⚠️ Scam Watch GaMarketplace: ${a.title}\n${a.desc}\nTips: ${tips.todo.join("; ")}.`;
      try { if (navigator.share) await navigator.share({ title: a.title, text }); else { await navigator.clipboard.writeText(text); toast("Warning copied. Paste it in your group chat."); } } catch {}
    }
  });

  /* ---- Check before you pay: searches verified reports ---- */
  const normalize = (v) => String(v).toLowerCase().trim().replace(/^https?:\/\//, "").replace(/^www\./, "").replace(/\/$/, "").replace(/^\+?62/, "0").replace(/(?<=\d)[\s.-](?=\d)/g, "");
  $("#checkForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const raw = $("#checkInput").value;
    if (!raw.trim()) return $("#checkInput").focus();
    const q = normalize(raw);
    const hits = Store.reports().filter((r) => r.status === "verified" && r.ident && (normalize(r.ident) === q || (q.length > 5 && (normalize(r.ident).includes(q) || q.includes(normalize(r.ident))))));
    const box = $("#checkResult");
    box.hidden = false;
    if (hits.length) {
      box.className = "checker__result checker__result--bad";
      box.innerHTML = `<i data-lucide="octagon-alert"></i><span><strong>Found in ${hits.length} verified report${hits.length > 1 ? "s" : ""}</strong> (${esc(TYPE_LABEL_SW[hits[0].type])}). Don't pay or click. <a href="#alert-${hits[0].id}">See the alert</a></span>`;
    } else {
      box.className = "checker__result checker__result--ok";
      box.innerHTML = `<i data-lucide="shield-check"></i><span><strong>No verified reports found.</strong> That's not a guarantee — still check the item in person and confirm payments in your own bank app.</span>`;
    }
    icons();
  });

  /* ---- Checklist (saved per browser) ---- */
  const CHECK_KEY = "gm-safety-checklist";
  function loadChecks() { try { return JSON.parse(localStorage.getItem(CHECK_KEY) || "[]"); } catch { return []; } }
  function updateChecklist(save) {
    const boxes = $$("#checklistItems input");
    if (save) try { localStorage.setItem(CHECK_KEY, JSON.stringify(boxes.map((b) => b.checked))); } catch {}
    const done = boxes.filter((b) => b.checked).length;
    $("#checklistText").textContent = `${done}/${boxes.length}`;
    $("#checklistBar").style.width = `${(done / boxes.length) * 100}%`;
  }
  loadChecks().forEach((v, i) => { const b = $$("#checklistItems input")[i]; if (b) b.checked = v; });
  $("#checklistItems").addEventListener("change", () => { updateChecklist(true); if ($$("#checklistItems input").every((b) => b.checked)) toast("✅ All set! You're ready for a safe meet-up."); });

  /* ---- Report dialog ---- */
  const dialog = $("#reportDialog");
  let evidence = []; // { url, file }
  function openReport(prefillTitle) {
    if (!requireLogin("Log in to report a scam.")) return;
    $("#reportForm").reset();
    $("#rAnon").checked = true;
    $("#rError").hidden = true;
    evidence.forEach((p) => URL.revokeObjectURL(p.url));
    evidence = [];
    $("#evidencePreview").innerHTML = "";
    $("#rDescCount").textContent = "0/500";
    const today = new Date().toISOString().slice(0, 10);
    $("#rDate").value = today; $("#rDate").max = today;
    if (prefillTitle) { $("#rType").value = "fake-item"; $("#rTitle").value = `Suspicious listing: ${prefillTitle}`.slice(0, 70); }
    dialog.showModal();
    $("#rTitle").focus();
  }
  [$("#heroReportBtn"), $("#panelReportBtn")].forEach((b) => b.addEventListener("click", () => openReport()));
  $("#rDesc").addEventListener("input", (e) => { $("#rDescCount").textContent = `${e.target.value.length}/500`; });
  $("#rEvidence").addEventListener("change", (e) => {
    [...e.target.files].forEach((f) => { if (f.type.startsWith("image/") && evidence.length < MAX_EVIDENCE) evidence.push({ url: URL.createObjectURL(f), file: f }); });
    e.target.value = "";
    $("#evidencePreview").innerHTML = evidence.map((p, i) => `<img src="${p.url}" alt="Evidence ${i + 1}" />`).join("");
  });
  $("#reportForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const err = $("#rError");
    const title = $("#rTitle").value.trim(), desc = $("#rDesc").value.trim();
    const fail = (msg, el) => { err.textContent = msg; err.hidden = false; el.focus(); };
    if (title.length < 5) return fail("Give your report a short title (at least 5 characters).", $("#rTitle"));
    if (desc.length < 20) return fail("Explain what happened in a bit more detail (at least 20 characters).", $("#rDesc"));
    try {
      const shots = [];
      for (const p of evidence) shots.push(await Store.compressImage(p.file, 900, 0.7));
      Store.createReport({ type: $("#rType").value, channel: $("#rChannel").value, place: $("#rPlace").value, date: $("#rDate").value, title, desc, ident: $("#rIdentifier").value.trim(), evidence: shots, anonymous: $("#rAnon").checked });
    } catch (ex) { return fail(ex.message || "Couldn't save the report.", $("#rTitle")); }
    dialog.close();
    setTab("mine");
    render();
    toast("Report sent. An admin will review it — you'll get a notification.");
  });

  /* ---- Controls ---- */
  function countActiveFilters() {
    const n = ["type", "severity", "channel"].filter((k) => view[k]).length;
    $("#filterCount").textContent = n;
    $("#filterCount").hidden = n === 0;
    $$("[data-filter]").forEach((s) => s.closest(".pill-select").classList.toggle("is-set", !!s.value));
  }
  function setTab(tab) {
    view.tab = tab;
    $$(".status-tab").forEach((t) => { const on = t.dataset.tab === tab; t.classList.toggle("is-active", on); t.setAttribute("aria-selected", String(on)); });
  }
  function resetFilters() {
    Object.assign(view, { search: "", type: "", severity: "", channel: "" });
    $("#alertSearch").value = "";
    $$("[data-filter]").forEach((s) => (s.value = ""));
    setTab("all"); countActiveFilters(); render();
  }
  $("#alertSearch").addEventListener("input", (e) => { view.search = e.target.value.trim().toLowerCase(); render(); });
  $("#sortSelect").addEventListener("change", (e) => { view.sort = e.target.value; render(); });
  $$("[data-filter]").forEach((sel) => sel.addEventListener("change", () => { view[sel.dataset.filter] = sel.value; countActiveFilters(); render(); }));
  $$(".status-tab").forEach((t) => t.addEventListener("click", () => { setTab(t.dataset.tab); render(); }));
  $("#resetBtn").addEventListener("click", resetFilters);
  $("#emptyResetBtn").addEventListener("click", resetFilters);
  $("#filterToggle").addEventListener("click", (e) => {
    const open = e.currentTarget.getAttribute("aria-expanded") !== "true";
    e.currentTarget.setAttribute("aria-expanded", String(open));
    $("#filterSelects").hidden = !open;
  });
  if (window.matchMedia("(max-width: 760px)").matches) { $("#filterToggle").setAttribute("aria-expanded", "false"); $("#filterSelects").hidden = true; }

  /* ---- Start ---- */
  const hashAlert = location.hash.match(/^#alert-(.+)$/);
  if (hashAlert) expanded.add(hashAlert[1]);
  if (location.hash === "#mine" && me()) setTab("mine");
  countActiveFilters();
  updateChecklist(false);
  render();
  Store.onChange(render);
  if (hashAlert) document.getElementById(`alert-${hashAlert[1]}`)?.scrollIntoView({ block: "center" });
  const params = new URLSearchParams(location.search);
  if (params.get("report")) openReport(params.get("about") || "");
});
