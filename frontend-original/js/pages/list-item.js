document.addEventListener("DOMContentLoaded", () => {
  /* ============ LIST AN ITEM ============ */
  // The listing is saved with Store (js/store.js) as "pending" and goes to the admin review queue.
  // With Laravel, this becomes a POST form (multipart, @csrf) to the backend's create-listing route.
  if (!requirePage()) return;
  const MAX_PHOTOS = 4; // photos are saved in the browser, so keep it small
  const MAX_SIZE = 5 * 1024 * 1024;
  const DRAFT_KEY = "gm-listing-draft";
  const COND_LABEL = CONDITION_LABEL;

  let photos = []; // [{ url, file }]
  const form = $("#listForm");

  const digits = (v) => Number(String(v).replace(/\D/g, "")) || 0;
  const fmtMoney = (input) => { const d = input.value.replace(/\D/g, ""); input.value = d ? Number(d).toLocaleString("id-ID") : ""; };
  const type = () => form.querySelector('input[name="type"]:checked').value;
  const cond = () => form.querySelector('input[name="condition"]:checked').value;

  /* ---- Type switch ---- */
  function syncType() {
    const t = type();
    $$("[data-for]").forEach((el) => (el.hidden = el.dataset.for !== t));
    $("#priceStepTitle").textContent = t === "auction" ? "Auction settings" : "Price";
    updatePreview();
  }
  form.addEventListener("change", (e) => { if (e.target.name === "type") syncType(); });

  /* ---- Photos ---- */
  function renderPhotos() {
    const grid = $("#photoGrid");
    $$(".photo-tile", grid).forEach((t) => t.remove());
    const add = $("#photoAdd");
    photos.forEach((p, i) => {
      const tile = document.createElement("div");
      tile.className = "photo-tile";
      tile.innerHTML = `
        <img src="${p.url}" alt="Photo ${i + 1}" />
        ${i === 0 ? '<span class="photo-tile__cover">Cover</span>' : ""}
        <div class="photo-tile__tools">
          <span>
            <button type="button" data-move="${i}" data-dir="-1" aria-label="Move photo ${i + 1} left" ${i === 0 ? "disabled" : ""}><i data-lucide="chevron-left"></i></button>
            <button type="button" data-move="${i}" data-dir="1" aria-label="Move photo ${i + 1} right" ${i === photos.length - 1 ? "disabled" : ""}><i data-lucide="chevron-right"></i></button>
          </span>
          <button type="button" data-remove="${i}" aria-label="Remove photo ${i + 1}"><i data-lucide="trash-2"></i></button>
        </div>`;
      grid.insertBefore(tile, add);
    });
    add.hidden = photos.length >= MAX_PHOTOS;
    icons();
    updatePreview();
  }
  function addPhotos(files) {
    const problems = [];
    for (const file of files) {
      if (photos.length >= MAX_PHOTOS) { problems.push(`Max ${MAX_PHOTOS} photos.`); break; }
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { problems.push(`“${file.name}” isn't a JPG, PNG or WEBP.`); continue; }
      if (file.size > MAX_SIZE) { problems.push(`“${file.name}” is bigger than 5 MB.`); continue; }
      photos.push({ url: URL.createObjectURL(file), file });
    }
    showError("photos", problems.join(" "));
    renderPhotos();
  }
  $("#photoInput").addEventListener("change", (e) => { addPhotos(e.target.files); e.target.value = ""; });
  const drop = $("#photoAdd");
  ["dragenter", "dragover"].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add("is-dragging"); }));
  ["dragleave", "drop"].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove("is-dragging")));
  drop.addEventListener("drop", (e) => { e.preventDefault(); addPhotos(e.dataTransfer.files); });
  $("#photoGrid").addEventListener("click", (e) => {
    const rm = e.target.closest("[data-remove]");
    if (rm) { const [p] = photos.splice(Number(rm.dataset.remove), 1); URL.revokeObjectURL(p.url); renderPhotos(); return; }
    const mv = e.target.closest("[data-move]");
    if (mv) {
      const i = Number(mv.dataset.move), j = i + Number(mv.dataset.dir);
      [photos[i], photos[j]] = [photos[j], photos[i]];
      renderPhotos();
      $(`[data-move="${j}"][data-dir="${mv.dataset.dir}"]`)?.focus();
    }
  });

  /* ---- Live preview ---- */
  function priceText() {
    const t = type();
    if (t === "auction") return digits($("#fStart").value) ? `Starting ${rupiah(digits($("#fStart").value))}` : "Starting bid –";
    return digits($("#fPrice").value) ? rupiah(digits($("#fPrice").value)) + ($("#fNego").checked ? " (nego)" : "") : "Rp –";
  }
  function updatePreview() {
    const title = $("#fTitle").value.trim();
    const h = $("#pvTitle");
    h.textContent = title || "Your item title";
    h.classList.toggle("is-placeholder", !title);
    $("#pvPrice").textContent = priceText();
    $("#pvCond").textContent = COND_LABEL[cond()];
    $("#pvTikum").textContent = $("#fTikum").value;
    $("#pvTag").textContent = TYPE_LABEL[type()];
    const media = $("#pvMedia");
    media.querySelector("img")?.remove();
    if (photos[0]) { const img = document.createElement("img"); img.src = photos[0].url; img.alt = ""; media.appendChild(img); }
  }

  /* ---- Money inputs + counters ---- */
  ["#fPrice", "#fStart", "#fBuyNow"].forEach((s) => $(s).addEventListener("input", (e) => fmtMoney(e.target)));
  $("#fTitle").addEventListener("input", (e) => ($("#titleCount").textContent = e.target.value.length));
  $("#fDesc").addEventListener("input", (e) => ($("#descCount").textContent = e.target.value.length));
  const ERROR_FOR = { fTitle: "title", fCategory: "category", fDesc: "desc", fPrice: "price", fStart: "start", fBuyNow: "buynow", fAgree: "agree" };
  // Hide a field's error as soon as the user edits it
  ["input", "change"].forEach((ev) => form.addEventListener(ev, (e) => {
    const key = ERROR_FOR[e.target.id];
    if (key) showError(key, "", e.target.type === "checkbox" ? null : e.target);
  }));
  form.addEventListener("input", () => { updatePreview(); saveDraft(); });
  form.addEventListener("change", () => { updatePreview(); saveDraft(); });

  /* ---- Draft (text fields only; photos aren't saved) ---- */
  const DRAFT_FIELDS = ["fTitle", "fCategory", "fDesc", "fPrice", "fStart", "fDuration", "fBuyNow", "fTikum"];
  function saveDraft() {
    const d = { type: type(), condition: cond(), nego: $("#fNego").checked };
    DRAFT_FIELDS.forEach((id) => (d[id] = $("#" + id).value));
    try { localStorage.setItem(DRAFT_KEY, JSON.stringify(d)); } catch {}
    $("#draftStatus").textContent = "Draft saved";
  }
  function loadDraft() {
    let d = null;
    try { d = JSON.parse(localStorage.getItem(DRAFT_KEY) || "null"); } catch {}
    $("#fTikum").value = me().tikum || me().faculty || "FEB";
    if (!d) return;
    DRAFT_FIELDS.forEach((id) => { if (d[id] !== undefined && d[id] !== null) $("#" + id).value = d[id]; });
    if (!$("#fCategory").value) $("#fCategory").selectedIndex = 0;
    form.querySelector(`input[name="type"][value="${d.type === "barter" ? "marketplace" : d.type}"]`)?.click();
    form.querySelector(`input[name="condition"][value="${d.condition}"]`)?.click();
    $("#fNego").checked = !!d.nego;
    $("#titleCount").textContent = $("#fTitle").value.length;
    $("#descCount").textContent = $("#fDesc").value.length;
    $("#draftStatus").textContent = "Draft restored";
  }
  $("#clearDraft").addEventListener("click", () => {
    if (!confirm("Clear everything in this form?")) return;
    resetForm();
    toast("Form cleared.");
  });
  function resetForm() {
    form.reset();
    photos.forEach((p) => URL.revokeObjectURL(p.url));
    photos = [];
    try { localStorage.removeItem(DRAFT_KEY); } catch {}
    $("#fCategory").selectedIndex = 0;
    $("#fTikum").value = me().tikum || me().faculty || "FEB";
    $("#titleCount").textContent = "0";
    $("#descCount").textContent = "0";
    $("#draftStatus").textContent = "";
    $$("[data-error]").forEach((e) => (e.hidden = true));
    $$(".is-invalid").forEach((e) => e.classList.remove("is-invalid"));
    renderPhotos();
    syncType();
  }

  /* ---- Validation ---- */
  function showError(key, msg, input) {
    const el = $(`[data-error="${key}"]`);
    el.textContent = msg || "";
    el.hidden = !msg;
    if (input) {
      const box = input.closest(".prefix-input") || input;
      box.classList.toggle("is-invalid", !!msg);
      if (msg) input.setAttribute("aria-invalid", "true"); else input.removeAttribute("aria-invalid");
    }
    return !msg;
  }
  function validate() {
    const t = type();
    const checks = [
      showError("photos", photos.length ? "" : "Add at least 1 photo of your item."),
      showError("title", $("#fTitle").value.trim().length >= 5 ? "" : "Title needs at least 5 characters.", $("#fTitle")),
      showError("category", $("#fCategory").value ? "" : "Choose a category.", $("#fCategory")),
      showError("desc", $("#fDesc").value.trim().length >= 20 ? "" : "Describe your item in at least 20 characters.", $("#fDesc")),
      showError("agree", $("#fAgree").checked ? "" : "Please confirm this is your item."),
    ];
    if (t === "marketplace") checks.push(showError("price", digits($("#fPrice").value) >= 1000 ? "" : "Enter a price (at least Rp 1.000).", $("#fPrice")));
    if (t === "auction") {
      const start = digits($("#fStart").value), buy = digits($("#fBuyNow").value);
      checks.push(showError("start", start >= 1000 ? "" : "Enter a starting bid (at least Rp 1.000).", $("#fStart")));
      checks.push(showError("buynow", !buy || buy > start ? "" : "Buy Now must be higher than the starting bid.", $("#fBuyNow")));
    }
    return checks.every(Boolean);
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!validate()) {
      const first = $$("[data-error]").find((x) => !x.hidden);
      first?.closest(".panel")?.scrollIntoView({ behavior: "smooth", block: "center" });
      toast("Please fix the highlighted fields.");
      return;
    }
    const btn = $("#submitBtn");
    btn.classList.add("is-loading");
    btn.querySelector("span").textContent = "Submitting…";

    const t = type();
    let listing;
    try {
      const photoData = [];
      for (const p of photos) photoData.push(await Store.compressImage(p.file));
      const data = {
        type: t,
        title: $("#fTitle").value.trim(),
        category: $("#fCategory").value,
        condition: cond(),
        desc: $("#fDesc").value.trim(),
        tikum: $("#fTikum").value,
        photos: photoData,
        price: t === "marketplace" ? digits($("#fPrice").value) : digits($("#fStart").value),
        nego: t === "marketplace" && $("#fNego").checked,
      };
      if (t === "auction") data.auction = { durationDays: Number($("#fDuration").value), buyNow: digits($("#fBuyNow").value) || null };
      listing = Store.createListing(data);
      try { localStorage.removeItem(DRAFT_KEY); } catch {}
    } catch (err) {
      btn.classList.remove("is-loading");
      btn.querySelector("span").textContent = "Submit for Review";
      toast(err.message || "Couldn't save your listing.");
      return;
    }

    setTimeout(() => {
      btn.classList.remove("is-loading");
      btn.querySelector("span").textContent = "Submit for Review";
      form.hidden = true;
      $("#doneName").textContent = `“${listing.title}”`;
      $("#doneCard").hidden = false;
      $("#doneCard").focus();
      window.scrollTo({ top: 0, behavior: "smooth" });
    }, 700);
  });

  $("#anotherBtn").addEventListener("click", () => {
    resetForm();
    $("#doneCard").hidden = true;
    form.hidden = false;
    $("#fTitle").focus();
  });

  /* ---- Start ---- */
  loadDraft();
  syncType();
  renderPhotos();
});
