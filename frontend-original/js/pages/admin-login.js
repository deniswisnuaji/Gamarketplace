/* ============ ADMIN LOGIN ============ */
// Checks the admin account saved by Store. Real admin access must be enforced by the backend.
document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) lucide.createIcons();
  const LOCK_KEY = "gm-admin-lock";
  const MAX_TRIES = 5, LOCK_SECONDS = 30;
  let tries = 0;

  const u = Store.currentUser();
  if (u && u.role === "admin") { location.replace("admin.html"); return; }

  function setError(id, msg) {
    const el = $(`[data-error="${id}"]`), input = document.getElementById(id);
    el.textContent = msg || ""; el.hidden = !msg;
    input.closest(".input-wrap").classList.toggle("is-invalid", !!msg);
    if (msg) input.setAttribute("aria-invalid", "true"); else input.removeAttribute("aria-invalid");
    return !msg;
  }
  function alertMsg(msg) {
    const a = $("#adminAlert");
    a.hidden = !msg;
    if (msg) { a.innerHTML = `<i data-lucide="circle-alert"></i><span>${msg}</span>`; icons(); }
  }
  ["adminEmail", "adminPassword"].forEach((id) => document.getElementById(id).addEventListener("input", () => setError(id, "")));

  $("#fillDemo").addEventListener("click", () => { $("#adminEmail").value = "admin@ugm.ac.id"; $("#adminPassword").value = "Admin2026"; $("#adminSubmit").focus(); });
  $("#eyeBtn").addEventListener("click", (e) => {
    const input = $("#adminPassword"), show = input.type === "password";
    input.type = show ? "text" : "password";
    e.currentTarget.setAttribute("aria-label", show ? "Hide password" : "Show password");
    e.currentTarget.innerHTML = `<i data-lucide="${show ? "eye-off" : "eye"}"></i>`;
    icons();
  });
  ["keydown", "keyup"].forEach((ev) => $("#adminPassword").addEventListener(ev, (e) => { if (e.getModifierState) $("#capsWarn").hidden = !e.getModifierState("CapsLock"); }));

  const lockedUntil = () => { try { return Number(localStorage.getItem(LOCK_KEY)) || 0; } catch { return 0; } };
  const LABEL = '<i data-lucide="log-in"></i><span>Sign in to Admin Panel</span>';
  function runLockTimer() {
    const btn = $("#adminSubmit");
    const tick = () => {
      const left = Math.ceil((lockedUntil() - Date.now()) / 1000);
      if (left <= 0) { btn.disabled = false; btn.innerHTML = LABEL; alertMsg(""); icons(); return; }
      btn.disabled = true;
      btn.innerHTML = `<span>Try again in ${left}s</span>`;
      setTimeout(tick, 1000);
    };
    tick();
  }
  if (lockedUntil() > Date.now()) { alertMsg("Too many wrong attempts. Please wait a moment."); runLockTimer(); }

  $("#adminForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    if (lockedUntil() > Date.now()) return;
    const email = $("#adminEmail").value.trim().toLowerCase();
    const pw = $("#adminPassword").value;
    alertMsg("");
    const ok = [
      setError("adminEmail", !email ? "Enter your admin email." : /^[^@\s]+@([a-z0-9-]+\.)*ugm\.ac\.id$/.test(email) ? "" : "Use a UGM email address."),
      setError("adminPassword", pw ? "" : "Enter your password."),
    ].every(Boolean);
    if (!ok) { $(".is-invalid input")?.focus(); return; }

    const btn = $("#adminSubmit");
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-circle"></i><span>Checking…</span>';
    icons();
    const res = await Store.login(email, pw, { admin: true, remember: $("#adminRemember").checked });
    btn.disabled = false;
    btn.innerHTML = LABEL;
    icons();
    if (res.error) {
      tries++;
      if (tries >= MAX_TRIES) {
        tries = 0;
        try { localStorage.setItem(LOCK_KEY, String(Date.now() + LOCK_SECONDS * 1000)); } catch {}
        alertMsg("Too many wrong attempts. Please wait 30 seconds.");
        runLockTimer();
        return;
      }
      alertMsg(res.error === "suspended" ? "This account is suspended." : `Email or password is incorrect, or this account has no admin access. (${MAX_TRIES - tries} tries left)`);
      $("#adminPassword").select();
      return;
    }
    const next = new URLSearchParams(location.search).get("next");
    location.href = next && /^admin\.html(#[\w-]+)?$/.test(next) ? next : "admin.html";
  });
});
