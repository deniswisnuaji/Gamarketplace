document.addEventListener("DOMContentLoaded", () => {
  /* ============ MEET-UP MAP ============ */
  // x / y = position on the schematic map in % (not real coordinates).
  // Directions open Google Maps with a search for the full name.
  const SPOTS = [
    { id: "balairung", short: "Balairung", name: "Balairung UGM", kind: "public", x: 50, y: 18, tip: "Meet in front of the main building, near the stairs." },
    { id: "perpustakaan", short: "Perpustakaan", name: "Perpustakaan Pusat UGM", kind: "public", x: 40, y: 47, tip: "Meet at the main entrance lobby." },
    { id: "student-center", short: "Student Center", name: "Student Center UGM", kind: "public", x: 61, y: 47, tip: "Busy with student activities most days." },
    { id: "boulevard", short: "Boulevard", name: "Boulevard UGM", kind: "public", x: 50, y: 70, tip: "Open area along the boulevard; pick a spot near the benches." },
    { id: "gsp", short: "GSP", name: "Grha Sabha Pramana UGM", kind: "public", x: 56, y: 30, tip: "Large open plaza in front of the building." },
    { id: "masjid", short: "Masjid Kampus", name: "Masjid Kampus UGM", kind: "public", x: 86, y: 70, tip: "Meet at the front yard, outside prayer times." },
    { id: "biologi", short: "Biologi", name: "Fakultas Biologi UGM", kind: "faculty", x: 31, y: 31 },
    { id: "feb", short: "FEB", name: "Fakultas Ekonomika dan Bisnis UGM", kind: "faculty", x: 68, y: 63 },
    { id: "farmasi", short: "Farmasi", name: "Fakultas Farmasi UGM", kind: "faculty", x: 20, y: 46 },
    { id: "filsafat", short: "Filsafat", name: "Fakultas Filsafat UGM", kind: "faculty", x: 82, y: 54 },
    { id: "geografi", short: "Geografi", name: "Fakultas Geografi UGM", kind: "faculty", x: 33, y: 62 },
    { id: "fh", short: "FH", name: "Fakultas Hukum UGM", kind: "faculty", x: 74, y: 40 },
    { id: "fib", short: "FIB", name: "Fakultas Ilmu Budaya UGM", kind: "faculty", x: 88, y: 40 },
    { id: "fisipol", short: "FISIPOL", name: "Fakultas Ilmu Sosial dan Ilmu Politik UGM", kind: "faculty", x: 70, y: 22 },
    { id: "fk-kmk", short: "FK-KMK", name: "Fakultas Kedokteran, Kesehatan Masyarakat, dan Keperawatan UGM", kind: "faculty", x: 10, y: 30 },
    { id: "fkg", short: "FKG", name: "Fakultas Kedokteran Gigi UGM", kind: "faculty", x: 16, y: 16 },
    { id: "fkh", short: "FKH", name: "Fakultas Kedokteran Hewan UGM", kind: "faculty", x: 24, y: 76 },
    { id: "fkt", short: "FKT", name: "Fakultas Kehutanan UGM", kind: "faculty", x: 14, y: 64 },
    { id: "fmipa", short: "FMIPA", name: "Fakultas Matematika dan Ilmu Pengetahuan Alam UGM", kind: "faculty", x: 36, y: 16 },
    { id: "faperta", short: "FAPERTA", name: "Fakultas Pertanian UGM", kind: "faculty", x: 40, y: 82 },
    { id: "fapet", short: "FAPET", name: "Fakultas Peternakan UGM", kind: "faculty", x: 12, y: 86 },
    { id: "psikologi", short: "Psikologi", name: "Fakultas Psikologi UGM", kind: "faculty", x: 84, y: 24 },
    { id: "ft", short: "FT", name: "Fakultas Teknik UGM", kind: "faculty", x: 62, y: 84 },
    { id: "ftp", short: "FTP", name: "Fakultas Teknologi Pertanian UGM", kind: "faculty", x: 30, y: 90 },
    { id: "sv", short: "SV", name: "Sekolah Vokasi UGM", kind: "faculty", x: 90, y: 86 },
    { id: "sps", short: "SPs", name: "Sekolah Pascasarjana UGM", kind: "faculty", x: 78, y: 10 },
  ];
  const FACULTY_TIP = "Meet at the faculty's main lobby or canteen area during the day.";
  const view = { search: "", kind: "all", active: null };
  const myTikum = () => (me() ? me().tikum : "");

  const directions = (s) => `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(s.name + ", Yogyakarta")}`;
  const isDefault = (s) => s.kind === "faculty" && myTikum() === s.short;
  const matchesView = (s) =>
    (view.kind === "all" || s.kind === view.kind) &&
    (!view.search || `${s.short} ${s.name}`.toLowerCase().includes(view.search));

  function renderPins() {
    $("#mapPins").innerHTML = SPOTS.map((s) => `
      <button type="button" class="pin pin--${s.kind}${s.id === view.active ? " is-active" : ""}${matchesView(s) ? "" : " is-dim"}${isDefault(s) ? " is-default" : ""}"
              style="left:${s.x}%;top:${s.y}%" data-spot="${s.id}" aria-label="${esc(s.name)}" ${matchesView(s) ? "" : 'tabindex="-1"'}>
        <span class="pin__dot"><i data-lucide="${s.kind === "public" ? "landmark" : "graduation-cap"}"></i></span>
        <span class="pin__label">${esc(s.short)}</span>
      </button>`).join("");
  }

  function renderList() {
    const list = SPOTS.filter(matchesView).sort((a, b) => (a.kind === b.kind ? a.short.localeCompare(b.short) : a.kind === "public" ? -1 : 1));
    ["all", "faculty", "public"].forEach((k) => {
      $(`[data-count="${k}"]`).textContent = SPOTS.filter((s) => (k === "all" || s.kind === k) && (!view.search || `${s.short} ${s.name}`.toLowerCase().includes(view.search))).length;
    });
    $("#spotList").innerHTML = list.length ? list.map((s) => `
      <li><button type="button" class="${s.kind === "public" ? "is-public" : ""}${s.id === view.active ? " is-active" : ""}" data-spot="${s.id}">
        <i data-lucide="${s.kind === "public" ? "landmark" : "graduation-cap"}"></i>
        <span><strong>${esc(s.short)}</strong>${isDefault(s) ? " ★" : ""}<small>${esc(s.name)}</small></span>
        <i data-lucide="chevron-right"></i>
      </button></li>`).join("") : '<li class="hint">No spots match your search.</li>';
  }

  function renderDetail() {
    const s = SPOTS.find((x) => x.id === view.active);
    const box = $("#spotDetail");
    if (!s) { box.innerHTML = '<p class="spot-detail__empty">Select a pin on the map or a spot in the list.</p>'; return; }
    box.innerHTML = `
      <div class="spot-detail__head">
        <span class="spot-detail__icon${s.kind === "public" ? " spot-detail__icon--public" : ""}"><i data-lucide="${s.kind === "public" ? "landmark" : "graduation-cap"}"></i></span>
        <div>
          <h2>${esc(s.short)}</h2>
          <p class="spot-detail__full">${esc(s.name)}</p>
          <div class="spot-detail__tags">
            <span class="mini-tag">${s.kind === "public" ? "Public spot" : "Faculty tikum"}</span>
            ${isDefault(s) ? '<span class="mini-tag mini-tag--default">★ Your default tikum</span>' : ""}
          </div>
        </div>
      </div>
      <p class="spot-detail__tip"><strong>Tip:</strong> ${esc(s.tip || FACULTY_TIP)}</p>
      <div class="spot-detail__actions">
        <a class="btn btn--primary" href="${directions(s)}" target="_blank" rel="noopener"><i data-lucide="navigation"></i><span>Directions</span></a>
        ${s.kind === "faculty" ? `
        <a class="btn btn--outline" href="marketplace.html?meetup=${encodeURIComponent(s.short)}"><i data-lucide="shopping-bag"></i><span>Items with this tikum</span></a>
        <button type="button" class="btn btn--outline" id="setDefault" ${isDefault(s) ? "disabled" : ""}><i data-lucide="star"></i><span>${isDefault(s) ? "Default tikum" : "Set as my default"}</span></button>` : ""}
        <button type="button" class="btn btn--outline" id="copySpot"><i data-lucide="copy"></i><span>Copy for chat</span></button>
      </div>`;
  }

  function renderAll() { renderPins(); renderList(); renderDetail(); icons(); }

  function select(id, { scroll = false } = {}) {
    view.active = id;
    history.replaceState(null, "", `#${id}`);
    renderAll();
    if (scroll && window.matchMedia("(max-width: 1280px)").matches) $("#spotDetail").scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  $("#mapPins").addEventListener("click", (e) => { const p = e.target.closest("[data-spot]"); if (p) select(p.dataset.spot, { scroll: true }); });
  $("#spotList").addEventListener("click", (e) => { const p = e.target.closest("[data-spot]"); if (p) select(p.dataset.spot, { scroll: true }); });
  $("#spotSearch").addEventListener("input", (e) => { view.search = e.target.value.trim().toLowerCase(); renderAll(); });
  $$(".chip[data-kind]").forEach((b) => b.addEventListener("click", () => {
    view.kind = b.dataset.kind;
    $$(".chip[data-kind]").forEach((x) => { x.classList.toggle("is-active", x === b); x.setAttribute("aria-pressed", String(x === b)); });
    renderAll();
  }));

  $("#spotDetail").addEventListener("click", async (e) => {
    const s = SPOTS.find((x) => x.id === view.active);
    if (e.target.closest("#setDefault")) {
      if (!requireLogin("Log in to save your default meet-up point.")) return;
      Store.updateUser(me().id, { tikum: s.short });
      renderAll();
      toast(`${s.short} is now your default meet-up point.`);
    }
    if (e.target.closest("#copySpot")) {
      const text = `Ketemuan di ${s.name} ya 📍 ${directions(s)}`;
      try { await navigator.clipboard.writeText(text); toast("Copied! Paste it in the chat."); }
      catch { toast("Couldn't copy. Long-press to copy the name instead."); }
    }
  });

  // Open a spot from the link (e.g. meetup-map.html#balairung or #ft)
  const fromHash = decodeURIComponent(location.hash.slice(1)).toLowerCase();
  view.active = SPOTS.some((s) => s.id === fromHash) ? fromHash : null;
  renderAll();
});
