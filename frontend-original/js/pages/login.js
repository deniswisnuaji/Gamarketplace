/* ============ LOGIN / REGISTER ============ */
// Accounts are stored by Store (js/store.js) in this browser.
// In Laravel: these forms POST to the auth routes with @csrf and the server checks the password.
document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) lucide.createIcons();

  const UGM_EMAIL = /^[a-z0-9._%+-]+@(mail\.)?ugm\.ac\.id$/i;
  const next = new URLSearchParams(location.search).get("next");
  const goNext = () => { location.href = next && /^[\w-]+\.html([?#].*)?$/.test(next) && !next.startsWith("login") ? next : "main.html"; };

  // Already logged in → skip this page
  if (Store.currentUser()) { goNext(); return; }

  function setError(id, msg) {
    const el = $(`[data-error="${id}"]`);
    const input = document.getElementById(id);
    el.textContent = msg || "";
    el.hidden = !msg;
    const wrap = input.closest(".input-wrap");
    if (wrap) wrap.classList.toggle("is-invalid", !!msg);
    if (msg) input.setAttribute("aria-invalid", "true"); else input.removeAttribute("aria-invalid");
    return !msg;
  }
  $$("input, select").forEach((el) => el.addEventListener("input", () => { if ($(`[data-error="${el.id}"]`)) setError(el.id, ""); }));

  /* ---- Tabs ---- */
  function showTab(which, focus = true) {
    const login = which === "login";
    $("#tabLogin").setAttribute("aria-selected", String(login));
    $("#tabRegister").setAttribute("aria-selected", String(!login));
    $("#loginForm").hidden = !login;
    $("#registerForm").hidden = login;
    $("#authTitle").textContent = login ? "Welcome back 👋" : "Join GaMarketplace";
    $("#authSub").textContent = login ? "Log in to buy, sell, and chat with UGM students." : "Create your account with your UGM email.";
    document.title = (login ? "Log in" : "Create account") + " — GaMarketplace";
    history.replaceState(null, "", location.pathname + location.search + (login ? "" : "#register"));
    if (focus) (login ? $("#loginEmail") : $("#regName")).focus();
  }
  $("#tabLogin").addEventListener("click", () => showTab("login"));
  $("#tabRegister").addEventListener("click", () => showTab("register"));
  $$("[data-go]").forEach((b) => b.addEventListener("click", () => showTab(b.dataset.go)));
  $(".auth-tabs").addEventListener("keydown", (e) => {
    if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
    const toRegister = $("#tabLogin").getAttribute("aria-selected") === "true";
    showTab(toRegister ? "register" : "login");
    (toRegister ? $("#tabRegister") : $("#tabLogin")).focus();
  });

  /* ---- Show / hide password + Caps Lock ---- */
  $$("[data-eye]").forEach((btn) => btn.addEventListener("click", () => {
    const input = document.getElementById(btn.dataset.eye);
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
    btn.innerHTML = `<i data-lucide="${show ? "eye-off" : "eye"}"></i>`;
    icons();
    input.focus();
  }));
  $$("[data-caps]").forEach((warn) => {
    const input = document.getElementById(warn.dataset.caps);
    ["keydown", "keyup"].forEach((ev) => input.addEventListener(ev, (e) => { if (e.getModifierState) warn.hidden = !e.getModifierState("CapsLock"); }));
    input.addEventListener("blur", () => (warn.hidden = true));
  });

  /* ---- Password strength ---- */
  function strength(pw) {
    let s = 0;
    if (pw.length >= 8) s++;
    if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
    if (/\d/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw) || pw.length >= 12) s++;
    return pw ? Math.max(1, s) : 0;
  }
  $("#regPassword").addEventListener("input", (e) => {
    const lvl = strength(e.target.value);
    $("#strength").dataset.level = lvl;
    $("#strengthText").textContent = ["Use 8+ characters with letters and numbers.", "Weak password", "Okay — add numbers or capitals", "Good password", "Strong password 💪"][lvl];
  });

  function busy(btn, on, label) {
    btn.classList.toggle("is-loading", on);
    btn.disabled = on;
    btn.innerHTML = on ? '<i data-lucide="loader-circle"></i><span>Please wait…</span>' : label;
    icons();
  }
  function showAlert(id, msg) {
    const a = $(id);
    a.hidden = !msg;
    if (msg) { a.innerHTML = `<i data-lucide="circle-alert"></i><span>${msg}</span>`; icons(); }
  }

  /* ---- Log in ---- */
  $("#loginForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = $("#loginEmail").value.trim();
    const pw = $("#loginPassword").value;
    showAlert("#loginAlert", "");
    const ok = [
      setError("loginEmail", !email ? "Enter your UGM email." : UGM_EMAIL.test(email) ? "" : "Use your UGM email (…@mail.ugm.ac.id)."),
      setError("loginPassword", pw ? "" : "Enter your password."),
    ].every(Boolean);
    if (!ok) { $(".is-invalid input")?.focus(); return; }
    const btn = $("#loginSubmit"), label = btn.innerHTML;
    busy(btn, true);
    const res = await Store.login(email, pw, { remember: $("#remember").checked });
    busy(btn, false, label);
    if (res.error === "wrong") { showAlert("#loginAlert", "Email or password is incorrect. New here? Create an account first."); $("#loginPassword").select(); return; }
    if (res.error === "admin") { showAlert("#loginAlert", 'This is an admin account. Please use the <a href="admin-login.html">admin sign in</a>.'); return; }
    if (res.error === "suspended") { showAlert("#loginAlert", "This account is suspended. Contact the GaMarketplace admin."); return; }
    toast(`Welcome back, ${res.user.name}!`);
    setTimeout(goNext, 500);
  });

  /* ---- Register ---- */
  $("#registerForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const name = $("#regName").value.trim();
    const email = $("#regEmail").value.trim();
    const pw = $("#regPassword").value;
    const ok = [
      setError("regName", name.length >= 3 ? "" : "Enter your full name."),
      setError("regEmail", !email ? "Enter your UGM email." : UGM_EMAIL.test(email) ? "" : "Only UGM emails (@mail.ugm.ac.id or @ugm.ac.id) can join."),
      setError("regFaculty", $("#regFaculty").value ? "" : "Choose your faculty."),
      setError("regPassword", pw.length < 8 ? "Password needs at least 8 characters." : /\d/.test(pw) && /[a-z]/i.test(pw) ? "" : "Use both letters and numbers."),
      setError("regConfirm", $("#regConfirm").value === pw && pw ? "" : "Passwords don't match."),
      setError("regAgree", $("#regAgree").checked ? "" : "Please agree to the safety rules."),
    ].every(Boolean);
    if (!ok) { $("#registerForm .is-invalid input, #registerForm .is-invalid select")?.focus(); return; }
    const btn = $("#registerSubmit"), label = btn.innerHTML;
    busy(btn, true);
    const res = await Store.register({ name, email, faculty: $("#regFaculty").value, password: pw });
    if (res.error) { busy(btn, false, label); setError("regEmail", res.error); return; }
    await Store.login(email, pw, { remember: true });
    toast("Account created! Welcome to GaMarketplace.");
    setTimeout(goNext, 700);
  });

  /* ---- Continue with UGM account (for now: user@mail.ugm.ac.id) ---- */
  $("#ssoBtn").addEventListener("click", async () => {
    const btn = $("#ssoBtn"), label = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = "<span>Connecting to UGM account…</span>";
    const res = await Store.loginWithUGM();
    btn.disabled = false;
    btn.innerHTML = label;
    if (res.error) { showAlert("#loginAlert", "This account is suspended. Contact the GaMarketplace admin."); return; }
    toast(`Welcome, ${res.user.name}!`);
    // First time: ask for faculty and meet-up point
    setTimeout(() => { if (!res.user.faculty) location.href = "settings.html?welcome=1#profile"; else goNext(); }, 500);
  });

  /* ---- Forgot password ---- */
  const fp = $("#fpDialog");
  $("#forgotBtn").addEventListener("click", () => {
    $("#fpEmail").value = $("#loginEmail").value;
    $("#fpDone").hidden = true;
    setError("fpEmail", "");
    $("#fpSubmit").hidden = false;
    fp.showModal();
    $("#fpEmail").focus();
  });
  $("#fpCancel").addEventListener("click", () => fp.close());
  $("#fpForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const email = $("#fpEmail").value.trim();
    if (!setError("fpEmail", UGM_EMAIL.test(email) ? "" : "Enter your UGM email.")) return;
    // Sending emails needs the backend. For now, explain honestly.
    $("#fpDone").querySelector("span").textContent = "Password reset by email needs the backend (Laravel). For now, ask the admin to help you, or create a new account.";
    $("#fpDone").hidden = false;
    $("#fpSubmit").hidden = true;
  });

  if (location.hash === "#register") showTab("register", false);
});
