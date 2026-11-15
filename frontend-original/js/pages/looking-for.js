document.addEventListener("DOMContentLoaded", () => {
  /* ============ LOOKING FOR ============ */
  // Requests are real posts from students (Store). Offers are real "I Have This" offers.
  const URGENT_DAYS = 3;
  const MAX_PHOTOS = 3, MAX_SIZE = 5 * 1024 * 1024;
  const view = { search: "", area: "", category: "", budget: "", faculty: "", condition: "", tab: "all", sort: "newest", activityAll: false };
  const COND_LABEL = { any: "Any condition", good: "Good or better", "like-new": "Like new only" };

  const daysLeft = (r) => Math.ceil((r.neededBy - Date.now()) / 86400e3);
  const isUrgent = (r) => !r.found && daysLeft(r) <= URGENT_DAYS;
  const isMine = (r) => { const u = me(); return u && r.userId === u.id; };
  const myOfferOn = (r) => { const u = me(); return u ? r.offers.find((o) => o.userId === u.id) : null; };
  const shortRp = (n) => (n >= 1000000 ? `${(n / 1000000).toLocaleString("id-ID")}jt` : `${Math.round(n / 1000)}rb`);
  const budgetText = (r) => (r.min ? `${rupiah(r.min)} – ${shortRp(r.max)}` : `Up to ${rupiah(r.max)}`);
  function neededText(r) {
    const d = daysLeft(r);
    if (d < 0) return "Date passed";
    if (d <= 1) return "Needed by tomorrow";
    if (d <= 7) return `Needed in ${d} days`;
    if (d >= 30) return "No rush";
    const w = Math.round(d / 7);
    return `Needed in ${w} week${w === 1 ? "" : "s"}`;
  }

  function passes(r) {
    const owner = Store.getUser(r.userId) || {};
    if (view.search && !`${r.title} ${r.desc}`.toLowerCase().includes(view.search)) return false;
    if (view.area && r.tikum !== view.area) return false;
    if (view.category && r.category !== view.category) return false;
    if (view.faculty && owner.faculty !== view.faculty) return false;
    if (view.condition && r.condition !== view.condition) return false;
    if (view.budget) {
      const [lo, hi] = view.budget.split("-").map((v) => (v ? Number(v) : null));
      if (lo !== null && r.max < lo) return false;
      if (hi !== null && r.min > hi) return false;
    }
    return true;
  }
  function inTab(r, tab) {
    if (tab === "urgent") return isUrgent(r);
    if (tab === "nooffers") return !r.found && r.offers.length === 0;
    if (tab === "mine") return isMine(r);
    return !r.found || isMine(r);
  }
  function sortList(list) {
    const key = { newest: (r) => -r.createdAt, soonest: (r) => r.neededBy, budget: (r) => -r.max, fewest: (r) => r.offers.length }[view.sort];
    return list.sort((a, b) => (!!a.found - !!b.found) || key(a) - key(b));
  }

  function cardHTML(r) {
    const owner = Store.getUser(r.userId);
    const u = me();
    const offered = myOfferOn(r);
    const saved = u && Store.isSaved(u.id, "request", r.id);
    const mine = isMine(r);
    const tags = [
      `<span class="cat-tag cat-tag--${r.category}">${CATEGORY_LABEL[r.category]}</span>`,
      r.found ? '<span class="pill-tag pill-tag--found"><i data-lucide="check"></i> Found</span>' : "",
      isUrgent(r) ? '<span class="pill-tag pill-tag--urgent"><i data-lucide="zap"></i> Urgent</span>' : "",
      mine && !r.found ? '<span class="pill-tag pill-tag--mine">Your request</span>' : "",
      offered ? '<span class="pill-tag pill-tag--offered">Offer sent</span>' : "",
    ].join("");
    let actions;
    if (mine) {
      actions = r.found
        ? `<button type="button" class="btn btn--outline" data-unfound="${r.id}"><i data-lucide="rotate-ccw"></i><span>Open again</span></button>
           <button type="button" class="btn btn--outline" data-delete="${r.id}" aria-label="Delete request"><i data-lucide="trash-2"></i></button>`
        : `<button type="button" class="btn btn--outline" data-found="${r.id}"><i data-lucide="circle-check"></i><span>Mark as Found</span></button>
           <button type="button" class="btn btn--primary" data-offer="${r.id}"><i data-lucide="inbox"></i><span>Offers (${r.offers.length})</span></button>`;
    } else if (r.found) {
      actions = '<button type="button" class="btn btn--outline" disabled><span>No longer needed</span></button>';
    } else {
      actions = offered
        ? `<button type="button" class="btn btn--outline is-done" data-offer="${r.id}"><i data-lucide="check"></i><span>Offer Sent</span></button>`
        : `<button type="button" class="btn btn--primary" data-offer="${r.id}"><i data-lucide="hand"></i><span>I Have This</span></button>`;
    }
    return `
      <article class="req-card${mine ? " is-mine" : ""}${r.found ? " is-found" : ""}" data-id="${r.id}">
        <div class="req-card__top">
          ${tags}
          ${mine ? "" : `<button class="wish-btn${saved ? " is-active" : ""}" type="button" data-save="${r.id}" aria-label="Save request ${esc(r.title)}" aria-pressed="${!!saved}"><i data-lucide="bookmark"></i></button>`}
        </div>
        ${r.photos && r.photos.length ? `
        <button type="button" class="req-photo" data-photos="${r.id}" aria-label="View reference photo for ${esc(r.title)}">
          <img src="${r.photos[0]}" alt="Reference photo: ${esc(r.title)}" loading="lazy" />
          <span class="req-photo__label">Reference photo</span>
          ${r.photos.length > 1 ? `<span class="req-photo__count"><i data-lucide="images"></i>${r.photos.length}</span>` : ""}
        </button>` : ""}
        <h3 class="req-card__title"><small>Looking for</small>${esc(r.title)}</h3>
        <p class="req-card__desc">${esc(r.desc || "No extra details.")}</p>
        <div class="budget"><span>Budget</span><strong>${budgetText(r)}</strong></div>
        <div class="req-card__meta">
          <span class="requester">${avatarHTML(owner, 22)}${esc(owner?.name || "Student")}</span>
          <span><i data-lucide="calendar"></i>${neededText(r)}</span>
          <span class="offers-count${r.offers.length ? "" : " is-zero"}">${r.offers.length} offer${r.offers.length === 1 ? "" : "s"}</span>
        </div>
        <div class="req-card__meta">
          <span><i data-lucide="shield-check"></i>${COND_LABEL[r.condition]}</span>
          <span><i data-lucide="map-pin"></i>${esc(r.tikum)}</span>
          <span><i data-lucide="clock"></i>${timeAgo(r.createdAt)}</span>
        </div>
        <div class="req-card__actions">${actions}</div>
      </article>`;
  }

  function render() {
    const all = Store.publicRequests();
    const base = all.filter(passes);
    ["all", "urgent", "nooffers", "mine"].forEach((t) => ($(`[data-tab-count="${t}"]`).textContent = base.filter((r) => inTab(r, t)).length));
    const list = sortList(base.filter((r) => inTab(r, view.tab)));
    $("#reqGrid").innerHTML = list.map(cardHTML).join("");
    const empty = $("#emptyState");
    empty.hidden = list.length > 0;
    empty.querySelector("strong").textContent = !all.length ? "No requests yet." : view.tab === "mine" ? "You haven't posted a request yet." : "No requests match these filters.";
    renderStats();
    renderTrending();
    renderActivity();
    icons();
  }
  function renderStats() {
    const u = me();
    const mine = u ? Store.requests().filter((r) => r.userId === u.id) : [];
    $("#statActive").textContent = mine.filter((r) => !r.found).length;
    $("#statOffers").textContent = mine.filter((r) => !r.found).reduce((s, r) => s + r.offers.length, 0);
    $("#statFound").textContent = mine.filter((r) => r.found).length;
  }
  // Most common words in open requests from the last 7 days
  function renderTrending() {
    const stop = new Set(["yang", "untuk", "dengan", "bekas", "second", "murah", "butuh", "cari", "size", "ukuran", "baru"]);
    const counts = {};
    Store.publicRequests().filter((r) => !r.found && Date.now() - r.createdAt < 7 * 86400e3).forEach((r) => {
      new Set(r.title.toLowerCase().split(/[^a-z0-9]+/).filter((w) => w.length >= 3 && !stop.has(w))).forEach((w) => (counts[w] = (counts[w] || 0) + 1));
    });
    const top = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 8);
    $("#trendChips").innerHTML = top.length
      ? top.map(([w, n]) => `<button type="button" data-q="${esc(w)}" class="${view.search === w ? "is-active" : ""}">${esc(w)} <small>(${n})</small></button>`).join("")
      : '<p class="hint">No requests this week yet.</p>';
  }
  function renderActivity() {
    const ev = [];
    Store.publicRequests().forEach((r) => {
      const owner = Store.getUser(r.userId)?.name || "A student";
      ev.push({ at: r.createdAt, icon: "search", html: `<strong>${esc(owner)}</strong> is looking for <strong>${esc(r.title)}</strong>` });
      r.offers.forEach((o) => ev.push({ at: o.at, icon: "hand", html: `<strong>${esc(Store.getUser(o.userId)?.name || "A student")}</strong> offered ${esc(o.item)} for <strong>${rupiah(o.price)}</strong>` }));
    });
    ev.sort((a, b) => b.at - a.at);
    const shown = ev.slice(0, view.activityAll ? 12 : 4);
    $("#activityList").innerHTML = shown.length ? shown.map((e) => `<li><span class="activity__icon"><i data-lucide="${e.icon}"></i></span><p class="activity__text">${e.html}</p><time>${timeAgo(e.at)}</time></li>`).join("") : '<li class="hint">No activity yet.</li>';
    $("#activityMoreBtn").hidden = ev.length <= 4;
  }

  /* ---- Offer dialog ("I Have This") — owners see the offers they received ---- */
  const offerDialog = $("#offerDialog"), postDialog = $("#postDialog");
  let openId = null;
  function openOffer(id) {
    const r = Store.getRequest(id);
    if (!r) return;
    openId = id;
    const owner = Store.getUser(r.userId);
    const cat = $("#ofCat");
    cat.className = `cat-tag cat-tag--${r.category}`;
    cat.textContent = CATEGORY_LABEL[r.category];
    $("#ofTitle").textContent = `Looking for: ${r.title}`;
    $("#ofOwner").textContent = owner?.name || "Student";
    $("#ofPlace").textContent = `${owner?.faculty || ""} · meet-up: ${r.tikum}`;
    $("#ofNeeded").textContent = neededText(r);
    $("#ofDesc").textContent = r.desc || "No extra details.";
    $("#ofBudget").textContent = r.min ? `${rupiah(r.min)} – ${rupiah(r.max)}` : `Up to ${rupiah(r.max)}`;
    $("#ofCond").textContent = COND_LABEL[r.condition];
    $("#ofCount").textContent = r.offers.length;
    const refBox = $("#ofPhotos");
    refBox.hidden = !(r.photos && r.photos.length);
    refBox.innerHTML = (r.photos || []).map((src, i) => `<button type="button" data-ref="${i}" aria-label="View reference photo ${i + 1}"><img src="${src}" alt="" /></button>`).join("");
    refBox.onclick = (ev) => { const b = ev.target.closest("[data-ref]"); if (b) openLightbox(r.photos, Number(b.dataset.ref), r.title); };

    const st = $("#ofStatus"), submit = $("#ofSubmit");
    const sent = myOfferOn(r);
    if (isMine(r)) {
      $("#ofFields").hidden = true;
      st.hidden = false;
      st.className = "bx-status offers-box";
      st.innerHTML = r.offers.length ? `<p><strong>Offers you received</strong></p>${r.offers.map((o) => {
        const s = Store.getUser(o.userId);
        return `<div class="offer-row">${avatarHTML(s, 32)}<div><strong>${esc(s?.name || "Student")}</strong> · ${esc(o.item)} · <strong>${rupiah(o.price)}</strong> (${CONDITION_LABEL[o.condition]})${o.msg ? `<br /><span class="hint">“${esc(o.msg)}”</span>` : ""}</div><a class="btn btn--outline btn--sm" href="messages.html?to=${o.userId}&request=${r.id}"><i data-lucide="message-circle-more"></i>Chat</a></div>`;
      }).join("")}` : "<p>No offers yet. Students will see your request on this page.</p>";
      submit.querySelector("span").textContent = "Close";
      submit.dataset.mode = "close";
    } else if (sent) {
      $("#ofFields").hidden = true;
      st.hidden = false;
      st.className = "bx-status bx-status--sent";
      st.textContent = `You offered "${sent.item}" for ${rupiah(sent.price)}. The requester can chat you if they're interested.`;
      submit.querySelector("span").textContent = "Withdraw Offer";
      submit.dataset.mode = "withdraw";
    } else {
      $("#ofFields").hidden = false;
      st.hidden = true;
      submit.querySelector("span").textContent = "Send Offer";
      submit.dataset.mode = "send";
    }
    $("#ofItem").value = "";
    $("#ofPrice").value = r.max;
    $("#ofMsg").value = "";
    $("#ofError").hidden = true;
    updatePriceHint();
    offerDialog.showModal();
    icons();
  }
  function updatePriceHint() {
    const r = Store.getRequest(openId);
    const price = Number($("#ofPrice").value);
    const hint = $("#ofPriceHint");
    hint.hidden = !(r && price > r.max);
    if (!hint.hidden) hint.textContent = `That's above their budget (max ${rupiah(r.max)}). You can still send it, but it may be declined.`;
  }
  $("#ofPrice").addEventListener("input", updatePriceHint);
  $("#offerForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const r = Store.getRequest(openId);
    const mode = $("#ofSubmit").dataset.mode;
    if (mode === "close") return offerDialog.close();
    if (!requireLogin("Log in to send an offer.")) return;
    if (mode === "withdraw") { Store.withdrawRequestOffer(r.id); offerDialog.close(); render(); return toast("Offer withdrawn."); }
    const item = $("#ofItem").value.trim();
    const price = Number($("#ofPrice").value);
    const err = $("#ofError");
    if (item.length < 3) { err.textContent = "Describe the item you have."; err.hidden = false; $("#ofItem").focus(); return; }
    if (!price || price <= 0) { err.textContent = "Enter your price."; err.hidden = false; $("#ofPrice").focus(); return; }
    const res = Store.offerOnRequest(r.id, { item, price, condition: $("#ofCondition").value, msg: $("#ofMsg").value.trim() });
    if (res.error) { err.textContent = res.error; err.hidden = false; return; }
    offerDialog.close();
    render();
    toast(`Offer sent to ${Store.getUser(r.userId)?.name || "the requester"}.`);
  });

  /* ---- Post a request ---- */
  let pendingPhotos = []; // { url, file }
  function renderPhotoPreviews() {
    $("#photoPreviews").innerHTML = pendingPhotos.map((p, i) => `
      <div class="photo-thumb"><img src="${p.url}" alt="Selected photo ${i + 1}" /><button type="button" data-remove-photo="${i}" aria-label="Remove photo ${i + 1}"><i data-lucide="x"></i></button></div>`).join("");
    $("#photoDrop").classList.toggle("is-full", pendingPhotos.length >= MAX_PHOTOS);
    icons();
  }
  function addPhotos(files) {
    const problems = [];
    for (const file of files) {
      if (pendingPhotos.length >= MAX_PHOTOS) { problems.push(`You can add up to ${MAX_PHOTOS} photos.`); break; }
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { problems.push(`"${file.name}" isn't a JPG, PNG or WEBP image.`); continue; }
      if (file.size > MAX_SIZE) { problems.push(`"${file.name}" is bigger than 5 MB.`); continue; }
      pendingPhotos.push({ url: URL.createObjectURL(file), file });
    }
    $("#pError").hidden = !problems.length;
    $("#pError").textContent = problems.join(" ");
    renderPhotoPreviews();
  }
  function clearPendingPhotos() { pendingPhotos.forEach((p) => URL.revokeObjectURL(p.url)); pendingPhotos = []; renderPhotoPreviews(); }
  $("#pPhotos").addEventListener("change", (e) => { addPhotos(e.target.files); e.target.value = ""; });
  $("#photoPreviews").addEventListener("click", (e) => {
    const b = e.target.closest("[data-remove-photo]");
    if (!b) return;
    const [p] = pendingPhotos.splice(Number(b.dataset.removePhoto), 1);
    URL.revokeObjectURL(p.url);
    renderPhotoPreviews();
  });
  const drop = $("#photoDrop");
  ["dragenter", "dragover"].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add("is-dragging"); }));
  ["dragleave", "drop"].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove("is-dragging")));
  drop.addEventListener("drop", (e) => { e.preventDefault(); addPhotos(e.dataTransfer.files); });

  function openPost() {
    if (!requireLogin("Log in to post a request.")) return;
    $("#postForm").reset();
    clearPendingPhotos();
    const u = me();
    if (u.faculty) $("#pFaculty").value = u.faculty;
    if (u.tikum) $("#pArea").value = u.tikum;
    $("#pError").hidden = true;
    postDialog.showModal();
    $("#pTitle").focus();
  }
  $("#postForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const err = $("#pError");
    const title = $("#pTitle").value.trim();
    const min = Number($("#pMin").value), max = Number($("#pMax").value);
    const fail = (msg, el) => { err.textContent = msg; err.hidden = false; el.focus(); };
    if (title.length < 3) return fail("Write what you're looking for (at least 3 characters).", $("#pTitle"));
    if (!max) return fail("Set the most you're willing to pay.", $("#pMax"));
    if (min > max) return fail("“Budget from” can't be higher than “Budget up to”.", $("#pMin"));
    if (!$("#pFaculty").value) return fail("Choose your faculty.", $("#pFaculty"));
    if (!$("#pArea").value) return fail("Choose a suggested meet-up point (tikum).", $("#pArea"));
    try {
      const photos = [];
      for (const p of pendingPhotos) photos.push(await Store.compressImage(p.file, 640, 0.7));
      Store.createRequest({
        title, category: $("#pCategory").value, min: min || 0, max, condition: $("#pCondition").value,
        tikum: $("#pArea").value, neededBy: Date.now() + Number($("#pDays").value) * 86400e3, desc: $("#pDesc").value.trim(), photos,
      });
    } catch (ex) { return fail(ex.message || "Couldn't save your request.", $("#pTitle")); }
    if (me().faculty !== $("#pFaculty").value) Store.updateUser(me().id, { faculty: $("#pFaculty").value });
    clearPendingPhotos();
    postDialog.close();
    setTab("mine");
    render();
    toast("Request posted! Students can now send you offers.");
  });
  [$("#heroPostBtn"), $("#panelPostBtn"), $("#emptyPostBtn")].forEach((b) => b.addEventListener("click", openPost));
  postDialog.addEventListener("close", () => { if (pendingPhotos.length) clearPendingPhotos(); });

  /* ---- Lightbox ---- */
  const lightbox = $("#lightbox");
  const lb = { photos: [], index: 0, title: "" };
  function showLb() {
    $("#lbImg").src = lb.photos[lb.index];
    $("#lbImg").alt = `Reference photo ${lb.index + 1} for ${lb.title}`;
    const many = lb.photos.length > 1;
    $("#lbPrev").hidden = !many; $("#lbNext").hidden = !many; $("#lbCount").hidden = !many;
    $("#lbCount").textContent = `${lb.index + 1} / ${lb.photos.length}`;
  }
  function openLightbox(photos, index, title) { Object.assign(lb, { photos, index, title }); showLb(); lightbox.showModal(); }
  const step = (d) => { lb.index = (lb.index + d + lb.photos.length) % lb.photos.length; showLb(); };
  $("#lbPrev").addEventListener("click", () => step(-1));
  $("#lbNext").addEventListener("click", () => step(1));
  lightbox.addEventListener("keydown", (e) => { if (e.key === "ArrowLeft") step(-1); if (e.key === "ArrowRight") step(1); });

  /* ---- Controls ---- */
  function countActiveFilters() {
    const n = ["category", "budget", "faculty", "condition"].filter((k) => view[k]).length;
    $("#filterCount").textContent = n;
    $("#filterCount").hidden = n === 0;
    $$("[data-filter]").forEach((s) => s.closest(".pill-select").classList.toggle("is-set", !!s.value));
  }
  function setTab(tab) {
    view.tab = tab;
    $$(".status-tab").forEach((t) => { const on = t.dataset.tab === tab; t.classList.toggle("is-active", on); t.setAttribute("aria-selected", String(on)); });
  }
  function resetFilters() {
    Object.assign(view, { search: "", area: "", category: "", budget: "", faculty: "", condition: "" });
    $("#reqSearch").value = ""; $("#areaSelect").value = "";
    $$("[data-filter]").forEach((s) => (s.value = ""));
    setTab("all"); countActiveFilters(); render();
  }
  $("#reqSearch").addEventListener("input", (e) => { view.search = e.target.value.trim().toLowerCase(); render(); });
  $("#areaSelect").addEventListener("change", (e) => { view.area = e.target.value; render(); });
  $("#sortSelect").addEventListener("change", (e) => { view.sort = e.target.value; render(); });
  $$("[data-filter]").forEach((sel) => sel.addEventListener("change", () => { view[sel.dataset.filter] = sel.value; countActiveFilters(); render(); }));
  $$(".status-tab").forEach((t) => t.addEventListener("click", () => {
    if (t.dataset.tab === "mine" && !requireLogin("Log in to see your requests.")) return;
    setTab(t.dataset.tab); render();
  }));
  $("#resetBtn").addEventListener("click", resetFilters);
  $("#emptyResetBtn").addEventListener("click", resetFilters);
  $("#filterToggle").addEventListener("click", (e) => {
    const open = e.currentTarget.getAttribute("aria-expanded") !== "true";
    e.currentTarget.setAttribute("aria-expanded", String(open));
    $("#filterSelects").hidden = !open;
  });
  if (window.matchMedia("(max-width: 760px)").matches) { $("#filterToggle").setAttribute("aria-expanded", "false"); $("#filterSelects").hidden = true; }
  $("#trendChips").addEventListener("click", (e) => {
    const chip = e.target.closest("button[data-q]");
    if (!chip) return;
    view.search = view.search === chip.dataset.q ? "" : chip.dataset.q;
    $("#reqSearch").value = view.search;
    setTab("all"); render();
  });
  $("#reqGrid").addEventListener("click", (e) => {
    const photo = e.target.closest("[data-photos]");
    if (photo) { const r = Store.getRequest(photo.dataset.photos); return openLightbox(r.photos, 0, r.title); }
    const offer = e.target.closest("[data-offer]");
    if (offer) { if (!isMine(Store.getRequest(offer.dataset.offer)) && !requireLogin("Log in to send an offer.")) return; return openOffer(offer.dataset.offer); }
    const save = e.target.closest("[data-save]");
    if (save) {
      if (!requireLogin("Log in to save requests.")) return;
      const on = Store.toggleSaved(me().id, "request", save.dataset.save);
      save.classList.toggle("is-active", on); save.setAttribute("aria-pressed", String(on));
      return toast(on ? "Request saved" : "Removed from saved");
    }
    const found = e.target.closest("[data-found]");
    if (found) {
      const r = Store.getRequest(found.dataset.found);
      if (!confirm(`Mark "${r.title}" as found? Students won't be able to send new offers.`)) return;
      Store.setRequestFound(r.id, true); render(); return toast("Nice! Request marked as found.");
    }
    const unfound = e.target.closest("[data-unfound]");
    if (unfound) { Store.setRequestFound(unfound.dataset.unfound, false); render(); return; }
    const del = e.target.closest("[data-delete]");
    if (del && confirm("Delete this request?")) { Store.deleteRequest(del.dataset.delete); render(); toast("Request deleted."); }
  });
  $("#activityMoreBtn").addEventListener("click", (e) => {
    view.activityAll = !view.activityAll;
    e.currentTarget.setAttribute("aria-expanded", String(view.activityAll));
    e.currentTarget.querySelector("span").textContent = view.activityAll ? "Show Less" : "See All Activity";
    renderActivity(); icons();
  });

  if (location.hash === "#mine" && me()) setTab("mine");
  countActiveFilters();
  render();
  Store.onChange(render);
});
