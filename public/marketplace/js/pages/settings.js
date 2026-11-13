document.addEventListener("DOMContentLoaded", () => {
  /* ============ SETTINGS ============ */
  // Edits the logged-in user's account in Store.
  if (!requirePage()) return;
  const PREF_DEFAULTS = { notifMessages: true, notifOffers: true, notifOutbid: true, notifPriceDrop: true, notifScam: true, notifEmail: false, verifiedOnly: false };
  const fields = { name: "#sName", username: "#sUsername", faculty: "#sFaculty", tikum: "#sTikum", bio: "#sBio", phone: "#sPhone" };
  const PRIVACY = ["showPhone", "showFaculty"];

  function snapshot() {
    const u = me();
    return {
      name: u.name, username: u.username || u.email.split("@")[0], faculty: u.faculty, tikum: u.tikum || u.faculty, bio: u.bio || "", phone: u.phone || "", photo: u.photo || "",
      showPhone: !!u.showPhone, showFaculty: u.showFaculty !== false, ...PREF_DEFAULTS, ...(u.prefs || {}),
    };
  }
  let saved = snapshot();
  let draft = { ...saved };

  function renderPhoto() {
    $("#photoPreview").innerHTML = draft.photo ? `<img src="${draft.photo}" alt="" />` : esc(initialsOf(draft.name || "?"));
    $("#photoRemove").hidden = !draft.photo;
  }
  function fillForm() {
    Object.entries(fields).forEach(([k, sel]) => ($(sel).value = draft[k] ?? ""));
    $$("[data-pref]").forEach((cb) => (cb.checked = !!draft[cb.dataset.pref]));
    $("#bioCount").textContent = (draft.bio || "").length;
    $("#sEmail").value = me().email;
    $("#sinceText").textContent = `Account created ${timeAgo(me().createdAt)}`;
    renderPhoto();
  }
  const isDirty = () => JSON.stringify(draft) !== JSON.stringify(saved);
  const updateSaveBar = () => { $("#saveBar").hidden = !isDirty(); };

  Object.entries(fields).forEach(([k, sel]) => $(sel).addEventListener("input", (e) => {
    let v = e.target.value;
    if (k === "username") { v = v.toLowerCase().replace(/[^a-z0-9._]/g, ""); e.target.value = v; }
    if (k === "phone") { v = v.replace(/[^\d+\- ]/g, ""); e.target.value = v; }
    draft[k] = v;
    if (k === "bio") $("#bioCount").textContent = v.length;
    if (k === "name") renderPhoto();
    updateSaveBar();
  }));
  $$("[data-pref]").forEach((cb) => cb.addEventListener("change", () => { draft[cb.dataset.pref] = cb.checked; updateSaveBar(); }));

  $("#photoInput").addEventListener("change", async (e) => {
    const file = e.target.files[0];
    e.target.value = "";
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return toast("Please choose a JPG, PNG or WEBP image.");
    if (file.size > 5 * 1024 * 1024) return toast("That photo is bigger than 5 MB.");
    try { draft.photo = await Store.compressImage(file, 256, 0.85); renderPhoto(); updateSaveBar(); }
    catch (err) { toast(err.message); }
  });
  $("#photoRemove").addEventListener("click", () => { draft.photo = ""; renderPhoto(); updateSaveBar(); });

  $("#settingsForm").addEventListener("submit", (e) => {
    e.preventDefault();
    if (draft.name.trim().length < 2) { $("#sName").focus(); return toast("Display name needs at least 2 characters."); }
    if (draft.username.length < 3) { $("#sUsername").focus(); return toast("Username needs at least 3 characters."); }
    if (!draft.faculty) { $("#sFaculty").focus(); return toast("Choose your faculty."); }
    if (!draft.tikum) { $("#sTikum").focus(); return toast("Choose your default meet-up point."); }
    if (Store.users().some((u) => u.id !== me().id && u.username === draft.username)) { $("#sUsername").focus(); return toast("That username is taken."); }
    if (draft.phone && draft.phone.replace(/\D/g, "").length < 10) { $("#sPhone").focus(); return toast("That WhatsApp number looks too short."); }
    if (draft.showPhone && !draft.phone) { $("#sPhone").focus(); return toast("Add a WhatsApp number first, or turn off “Show my WhatsApp number”."); }
    const prefs = {};
    Object.keys(PREF_DEFAULTS).forEach((k) => (prefs[k] = !!draft[k]));
    try {
      Store.updateUser(me().id, {
        name: draft.name.trim(), username: draft.username, faculty: draft.faculty, tikum: draft.tikum, bio: draft.bio.trim(),
        phone: draft.phone.trim(), photo: draft.photo, showPhone: !!draft.showPhone, showFaculty: !!draft.showFaculty, prefs,
      });
    } catch (err) { return toast(err.message || "Couldn't save. Try a smaller photo."); }
    saved = snapshot(); draft = { ...saved };
    updateSaveBar();
    renderSessionUI();
    toast("Settings saved.");
    if (new URLSearchParams(location.search).get("welcome")) setTimeout(() => (location.href = "main.html"), 700);
  });
  $("#discardBtn").addEventListener("click", () => { draft = { ...saved }; fillForm(); updateSaveBar(); });
  window.addEventListener("beforeunload", (e) => { if (isDirty()) { e.preventDefault(); e.returnValue = ""; } });

  // Change password
  $("#pwSave").addEventListener("click", async () => {
    const err = $("#pwError");
    const oldPw = $("#pwOld").value, newPw = $("#pwNew").value;
    const fail = (m) => { err.textContent = m; err.hidden = false; };
    if (!oldPw) return fail("Enter your current password.");
    if (newPw.length < 8 || !/\d/.test(newPw) || !/[a-z]/i.test(newPw)) return fail("New password needs 8+ characters with letters and numbers.");
    const res = await Store.changePassword(me().id, oldPw, newPw);
    if (res.error) return fail(res.error);
    err.hidden = true; $("#pwOld").value = ""; $("#pwNew").value = "";
    toast("Password updated.");
  });

  // Highlight the section you're looking at
  const links = $$(".settings-nav__link");
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((en) => { if (en.isIntersecting) links.forEach((l) => l.classList.toggle("is-active", l.getAttribute("href") === `#${en.target.id}`)); });
  }, { rootMargin: "-30% 0px -60% 0px" });
  $$(".settings-card").forEach((sec) => observer.observe(sec));

  // Delete account
  const delDialog = $("#deleteDialog");
  $("#deleteBtn").addEventListener("click", () => { $("#deleteConfirm").value = ""; $("#deleteSubmit").disabled = true; delDialog.showModal(); $("#deleteConfirm").focus(); });
  $("#deleteConfirm").addEventListener("input", (e) => { $("#deleteSubmit").disabled = e.target.value.trim() !== "DELETE"; });
  $("#deleteForm").addEventListener("submit", (e) => {
    e.preventDefault();
    if ($("#deleteConfirm").value.trim() !== "DELETE") return;
    const id = me().id;
    Store.logout();
    Store.deleteUser(id);
    saved = draft; // no unsaved-changes warning
    location.href = "main.html";
  });

  fillForm();
  // Coming from "Continue with UGM account" for the first time
  if (new URLSearchParams(location.search).get("welcome")) {
    toast("Welcome! Choose your faculty and default meet-up point, then save.");
    $("#sFaculty").focus();
  }
});
