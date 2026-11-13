document.addEventListener("DOMContentLoaded", () => {
  /* ============ ADMIN PANEL ============ */
  // Everything here comes from Store (what students and admins really did).
  // The role check below runs in the browser only — the backend must enforce admin access.
  const admin = me();
  if (!admin || admin.role !== "admin") { location.replace("admin-login.html?next=" + encodeURIComponent("admin.html" + location.hash)); return; }

  $("#adminNameSide").textContent = admin.name;
  $("#adminNameMenu").textContent = admin.name;
  $("#adminEmailMenu").textContent = admin.email;
  $("#adminAvatar").textContent = initialsOf(admin.name);
  function logout() { Store.logout(); location.href = "admin-login.html"; }
  $("#adminLogout").addEventListener("click", logout);
  $("#adminLogout2").addEventListener("click", (e) => { e.preventDefault(); logout(); });

  /* ---------- Helpers ---------- */
  const STATUS = { approved: "Live", pending: "In review", rejected: "Rejected", hidden: "Hidden" };
  const seller = (l) => Store.getUser(l.sellerId) || { name: "Deleted user", faculty: "", createdAt: 0, status: "deleted" };
  const priceOf = (l) => (l.type === "barter" ? "Barter" : l.type === "auction" ? `From ${rupiah(l.price)}` : rupiah(l.price));
  const students = () => Store.users().filter((u) => u.role === "student");
  // Reports that mention this user (by email or name in the "phone / account / link" field)
  const reportsAbout = (u) => Store.reports().filter((r) => r.status !== "dismissed" && r.ident && (r.ident.toLowerCase().includes(u.email.toLowerCase()) || r.ident.toLowerCase().includes(u.name.toLowerCase()))).length;

  // Simple warning signs to help moderators (computed from real data)
  function warningsFor(l) {
    const w = [];
    const s = seller(l);
    if (Date.now() - s.createdAt < 86400e3) w.push("Account created today");
    if ((l.photos || []).length < 2) w.push("Only 1 photo");
    if ((l.desc || "").length < 40) w.push("Very short description");
    if (Store.listings().some((x) => x.sellerId === l.sellerId && x.moderation === "rejected" && x.id !== l.id)) w.push("Seller had a rejected listing");
    if (reportsAbout(s)) w.push("Seller was reported");
    const same = Store.publicListings(l.type).filter((x) => x.category === l.category && x.price > 0);
    if (l.price > 0 && same.length >= 3) {
      const sorted = same.map((x) => x.price).sort((a, b) => a - b);
      const median = sorted[Math.floor(sorted.length / 2)];
      if (l.price < median * 0.3) w.push("Price much lower than similar items");
    }
    return w;
  }

  function renderLog() {
    const labels = { approve: "Approved", reject: "Rejected", edit: "Edited", delete: "Removed", suspend: "Suspended", verify: "Verified", info: "Info" };
    const log = Store.adminLog();
    $("#activityLog").innerHTML = log.length ? log.slice(0, 40).map((e) =>
      `<li><span class="tag-mini tag-mini--${e.kind}">${labels[e.kind] || "Info"}</span><span>${esc(e.text)}${e.adminId ? ` <span class="hint">· ${esc(Store.getUser(e.adminId)?.name || "admin")}</span>` : ""}</span><time>${timeAgo(e.at)}</time></li>`).join("")
      : '<li class="empty">No admin actions yet.</li>';
  }

  /* ---------- Routing ---------- */
  const TITLES = { dashboard: "Dashboard", review: "Listing Review", listings: "All Listings", reports: "Scam Reports", users: "Users" };
  function route() {
    const v = TITLES[location.hash.slice(1)] ? location.hash.slice(1) : "dashboard";
    $$(".admin-view").forEach((s) => (s.hidden = s.dataset.view !== v));
    $$("[data-nav]").forEach((a) => {
      const on = a.dataset.nav === v;
      a.classList.toggle("is-active", on);
      if (on) a.setAttribute("aria-current", "page"); else a.removeAttribute("aria-current");
    });
    $("#pageTitle").textContent = TITLES[v];
    document.title = `${TITLES[v]} — GaMarketplace Admin`;
    renderAll();
    if (window.matchMedia("(max-width: 1024px)").matches && !document.body.classList.contains("sidebar-collapsed")) $("#sidebarClose").click();
    window.scrollTo(0, 0);
  }
  window.addEventListener("hashchange", route);

  /* ---------- Dashboard ---------- */
  function renderDashboard() {
    const all = Store.listings();
    const pending = all.filter((l) => l.moderation === "pending");
    const openReports = Store.reports().filter((r) => r.status === "review");
    $("#kPending").textContent = pending.length;
    $("#kLive").textContent = all.filter((l) => Store.isPublic(l)).length;
    $("#kReports").textContent = openReports.length;
    $("#kUsers").textContent = students().length;
    const navR = $("#navReview"), navP = $("#navReports");
    navR.textContent = pending.length; navR.classList.toggle("is-zero", !pending.length);
    navP.textContent = openReports.length; navP.classList.toggle("is-zero", !openReports.length);

    // New listings per day (last 7 days), from real createdAt dates
    const start = new Date(); start.setHours(0, 0, 0, 0);
    const days = [...Array(7)].map((_, i) => new Date(start.getTime() - (6 - i) * 86400e3));
    const counts = days.map((d) => all.filter((l) => l.createdAt >= d.getTime() && l.createdAt < d.getTime() + 86400e3).length);
    const max = Math.max(1, ...counts);
    $("#bars").innerHTML = counts.map((c, i) => `
      <div class="bar${i === 6 ? " is-today" : ""}"><div class="bar__fill" style="height:${Math.max(2, (c / max) * 85)}%"><span>${c}</span></div><small>${i === 6 ? "Today" : days[i].toLocaleDateString("en-GB", { weekday: "short" })}</small></div>`).join("");
    $("#bars").setAttribute("aria-label", "New listings per day: " + counts.map((c, i) => `${i === 6 ? "today" : days[i].toLocaleDateString("en-GB", { weekday: "long" })} ${c}`).join(", "));

    const items = [];
    const flagged = pending.filter((l) => warningsFor(l).length >= 2);
    if (flagged.length) items.push(`<a href="#review"><i data-lucide="triangle-alert" class="dot-red"></i><span><strong>${flagged.length}</strong> pending listing${flagged.length > 1 ? "s" : ""} with several warning signs</span><i data-lucide="chevron-right"></i></a>`);
    const high = openReports.filter((r) => r.risk === "high");
    if (high.length) items.push(`<a href="#reports"><i data-lucide="shield-alert" class="dot-red"></i><span><strong>${high.length}</strong> high-risk scam report${high.length > 1 ? "s" : ""} to check</span><i data-lucide="chevron-right"></i></a>`);
    const old = pending.filter((l) => Date.now() - l.updatedAt > 2 * 3600e3);
    if (old.length) items.push(`<a href="#review"><i data-lucide="clock" class="dot-yellow"></i><span><strong>${old.length}</strong> listing${old.length > 1 ? "s" : ""} waiting more than 2 hours</span><i data-lucide="chevron-right"></i></a>`);
    if (pending.length && !flagged.length && !old.length) items.push(`<a href="#review"><i data-lucide="hourglass" class="dot-yellow"></i><span><strong>${pending.length}</strong> listing${pending.length > 1 ? "s" : ""} waiting for review</span><i data-lucide="chevron-right"></i></a>`);
    const reported = students().filter((u) => u.status === "active" && reportsAbout(u) >= 2);
    if (reported.length) items.push(`<a href="#users"><i data-lucide="user-x" class="dot-blue"></i><span><strong>${reported.length}</strong> active user${reported.length > 1 ? "s" : ""} reported 2+ times</span><i data-lucide="chevron-right"></i></a>`);
    $("#attention").innerHTML = items.length ? items.map((x) => `<li>${x}</li>`).join("") : '<li class="empty">All clear ✨</li>';
    renderLog();
  }

  /* ---------- Listing review ---------- */
  const selected = new Set();
  const rv = { search: "", type: "" };
  function renderReview() {
    const list = Store.listings().filter((l) => l.moderation === "pending")
      .filter((l) => (!rv.type || l.type === rv.type) && (!rv.search || `${l.title} ${seller(l).name}`.toLowerCase().includes(rv.search)))
      .sort((a, b) => a.updatedAt - b.updatedAt);
    [...selected].forEach((id) => { if (!list.some((l) => l.id === id)) selected.delete(id); });
    $("#reviewList").innerHTML = list.map((l) => {
      const s = seller(l);
      const extra = l.type === "auction" ? `<span class="chip-t">${l.auction.durationDays} day auction${l.auction.buyNow ? " · Buy Now " + rupiah(l.auction.buyNow) : ""}</span>`
        : l.type === "barter" ? `<span class="chip-t">${l.barter.swap === "open" ? "Open to offers" : "Wants: " + esc(l.barter.want) + (l.barter.cash ? " + " + rupiah(l.barter.cash) : "")}</span>` : "";
      return `
      <li class="rv${selected.has(l.id) ? " is-selected" : ""}" data-id="${l.id}">
        <div class="rv__check"><input type="checkbox" data-select="${l.id}" ${selected.has(l.id) ? "checked" : ""} aria-label="Select ${esc(l.title)}" /></div>
        <div class="rv__media">${listingPhoto(l) ? `<img src="${listingPhoto(l)}" alt="" />` : '<i data-lucide="image-off"></i>'}<span class="count">${(l.photos || []).length} photo${(l.photos || []).length === 1 ? "" : "s"}</span></div>
        <div class="rv__body">
          <div class="rv__tags">
            <span class="chip-t">${TYPE_LABEL[l.type]}</span>
            <span class="chip-t">${CATEGORY_LABEL[l.category] || esc(l.category)}</span>
            <span class="chip-t">${CONDITION_LABEL[l.condition] || ""}</span>
            ${extra}
            ${l.approvedAt ? '<span class="chip-t chip-t--new">Edited after approval</span>' : ""}
            ${warningsFor(l).map((f) => `<span class="chip-t chip-t--warn"><i data-lucide="triangle-alert"></i>${esc(f)}</span>`).join("")}
          </div>
          <h3 class="rv__title">${esc(l.title)}</h3>
          <span class="rv__price">${priceOf(l)}</span>
          <p class="rv__desc">${esc(l.desc || "No description.")}</p>
          <div class="rv__meta">
            <span><i data-lucide="user-round"></i>${esc(s.name)}${s.faculty ? " · " + esc(s.faculty) : ""}</span>
            <span><i data-lucide="map-pin"></i>Tikum ${esc(l.tikum)}</span>
            <span><i data-lucide="clock"></i>Submitted ${timeAgo(l.updatedAt)}</span>
          </div>
        </div>
        <div class="rv__actions">
          <button type="button" class="btn btn--approve" data-approve="${l.id}"><i data-lucide="check"></i><span>Approve</span></button>
          <button type="button" class="btn btn--reject" data-reject="${l.id}"><i data-lucide="x"></i><span>Reject</span></button>
          <button type="button" class="btn btn--outline" data-detail="${l.id}"><i data-lucide="pencil"></i><span>View / Edit</span></button>
        </div>
      </li>`;
    }).join("");
    $("#reviewEmpty").hidden = list.length > 0;
    $("#bulkBar").hidden = selected.size === 0;
    $("#bulkCount").textContent = selected.size;
  }
  function leave(id, done) { const c = $(`.rv[data-id="${id}"]`); if (c) { c.classList.add("is-leaving"); setTimeout(done, 220); } else done(); }
  function approve(ids) {
    ids.forEach((id) => Store.moderate(id, "approved"));
    renderAll();
    toast(ids.length > 1 ? `${ids.length} listings approved and now live.` : `Approved: ${Store.getListing(ids[0]).title}`);
  }
  $("#reviewList").addEventListener("change", (e) => {
    const cb = e.target.closest("[data-select]");
    if (!cb) return;
    cb.checked ? selected.add(cb.dataset.select) : selected.delete(cb.dataset.select);
    cb.closest(".rv").classList.toggle("is-selected", cb.checked);
    $("#bulkBar").hidden = selected.size === 0;
    $("#bulkCount").textContent = selected.size;
  });
  $("#reviewList").addEventListener("click", (e) => {
    const a = e.target.closest("[data-approve]");
    if (a) { const id = a.dataset.approve; leave(id, () => approve([id])); return; }
    const r = e.target.closest("[data-reject]");
    if (r) return openReject(Store.getListing(r.dataset.reject));
    const d = e.target.closest("[data-detail]");
    if (d) openDetail(Store.getListing(d.dataset.detail));
  });
  $("#bulkApprove").addEventListener("click", () => { const ids = [...selected]; selected.clear(); approve(ids); });
  $("#bulkClear").addEventListener("click", () => { selected.clear(); renderReview(); icons(); });
  $("#rvSearch").addEventListener("input", (e) => { rv.search = e.target.value.trim().toLowerCase(); renderReview(); icons(); });
  $("#rvType").addEventListener("change", (e) => { rv.type = e.target.value; renderReview(); icons(); });

  /* Reject */
  const REASONS = ["Photo is unclear or not the real item", "Item is not allowed (medicine, weapons, alcohol, etc.)", "Price or description is misleading", "Duplicate listing", "Looks like a business / reseller, not a student", "Other"];
  const rejectDialog = $("#rejectDialog");
  let rejecting = null;
  $("#rjReasons").innerHTML = REASONS.map((r, i) => `<label><input type="radio" name="rjReason" value="${esc(r)}" ${i === 0 ? "checked" : ""} /> ${esc(r)}</label>`).join("");
  function openReject(l) {
    rejecting = l;
    $("#rjItem").textContent = `“${l.title}” by ${seller(l).name}`;
    $("#rjNote").value = "";
    $("#rjError").hidden = true;
    rejectDialog.showModal();
  }
  $("#rejectForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const reason = $('input[name="rjReason"]:checked').value;
    const note = $("#rjNote").value.trim();
    if (reason === "Other" && note.length < 10) { $("#rjError").textContent = "Write a short note (10+ characters) so the seller knows what to fix."; $("#rjError").hidden = false; return; }
    const l = rejecting;
    rejectDialog.close();
    leave(l.id, () => {
      Store.moderate(l.id, "rejected", note ? `${reason}. ${note}` : reason);
      renderAll();
      toast(`Rejected: ${l.title}. The seller was notified.`);
    });
  });

  /* Detail / edit */
  const detailDialog = $("#detailDialog");
  let editing = null;
  function openDetail(l) {
    editing = l;
    const s = seller(l);
    $("#dtTitle").textContent = l.moderation === "pending" ? "Review listing" : "Listing details";
    $("#dtPhotos").innerHTML = (l.photos || []).map((p) => `<img src="${p}" alt="" />`).join("");
    $("#dtMeta").innerHTML = `
      <span class="status status--${l.moderation}">${STATUS[l.moderation]}</span>
      <span>${TYPE_LABEL[l.type]}</span> · <span>${esc(s.name)} (${esc(s.email || "")})</span> · <span>Tikum ${esc(l.tikum)}</span>
      ${l.reason ? `<p class="hint">Admin note: ${esc(l.reason)}</p>` : ""}`;
    $("#dtTitleInput").value = l.title;
    $("#dtPrice").value = l.price ? l.price.toLocaleString("id-ID") : "";
    $("#dtPrice").disabled = l.type === "barter" || (l.type === "auction" && l.auction.bids.length > 0);
    $("#dtCategory").value = l.category;
    $("#dtDesc").value = l.desc || "";
    $("#dtError").hidden = true;
    detailDialog.showModal();
  }
  $("#dtPrice").addEventListener("input", (e) => { const d = e.target.value.replace(/\D/g, ""); e.target.value = d ? Number(d).toLocaleString("id-ID") : ""; });
  $("#detailForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const title = $("#dtTitleInput").value.trim();
    const price = Number($("#dtPrice").value.replace(/\D/g, ""));
    if (title.length < 5) { $("#dtError").textContent = "Title needs at least 5 characters."; $("#dtError").hidden = false; return; }
    if (editing.type !== "barter" && price < 1000) { $("#dtError").textContent = "Price must be at least Rp 1.000."; $("#dtError").hidden = false; return; }
    Store.updateListing(editing.id, { title, price: editing.type === "barter" ? 0 : price, category: $("#dtCategory").value, desc: $("#dtDesc").value.trim() });
    Store.log("edit", title);
    Store.notify(editing.sellerId, "info", `An admin made a small edit to your listing “${title}”.`, "my-listings.html");
    Store.save();
    detailDialog.close();
    renderAll();
    toast("Changes saved. The seller was notified.");
  });

  /* ---------- All listings ---------- */
  const al = { search: "", status: "", type: "" };
  function renderListings() {
    const all = Store.listings();
    const list = all
      .filter((l) => (!al.status || l.moderation === al.status) && (!al.type || l.type === al.type))
      .filter((l) => !al.search || `${l.title} ${seller(l).name} ${l.tikum}`.toLowerCase().includes(al.search))
      .sort((a, b) => b.createdAt - a.createdAt);
    $("#listingBody").innerHTML = list.map((l) => `
      <tr>
        <td data-label="Item"><div class="cell-item">${listingPhoto(l) ? `<img src="${listingPhoto(l)}" alt="" />` : '<span class="ph" aria-hidden="true"></span>'}<div><strong><a href="item.html?id=${l.id}" target="_blank" rel="noopener">${esc(l.title)}</a></strong><small>Tikum ${esc(l.tikum)} · ${timeAgo(l.createdAt)}</small></div></div></td>
        <td data-label="Seller">${esc(seller(l).name)}</td>
        <td data-label="Type">${TYPE_LABEL[l.type]}</td>
        <td data-label="Price">${l.type === "auction" ? rupiah(Store.currentPrice(l)) : priceOf(l)}</td>
        <td data-label="Status"><span class="status status--${l.moderation}">${STATUS[l.moderation]}</span></td>
        <td data-label="Availability">${l.moderation === "approved" && l.type !== "auction" ? `
          <label class="sr-only" for="av-${l.id}">Availability of ${esc(l.title)}</label>
          <select class="mini-select" id="av-${l.id}" data-avail="${l.id}">
            ${["available", "reserved", "sold"].map((a) => `<option value="${a}" ${l.availability === a ? "selected" : ""}>${a[0].toUpperCase() + a.slice(1)}</option>`).join("")}
          </select>` : l.type === "auction" && l.moderation === "approved" ? (Store.auctionEnded(l) ? "Ended" : "Running") : "—"}</td>
        <td data-label="Saves">${Store.users().filter((u) => (u.wishlist || []).some((w) => w.id === l.id)).length}</td>
        <td data-label="Actions"><div class="row-actions">
          ${l.moderation === "pending" ? `<button type="button" class="icon-action" data-goreview aria-label="Review ${esc(l.title)}"><i data-lucide="clipboard-check"></i></button>` : ""}
          <button type="button" class="icon-action" data-detail="${l.id}" aria-label="Edit ${esc(l.title)}"><i data-lucide="pencil"></i></button>
          ${l.moderation === "approved" ? `<button type="button" class="icon-action" data-hide="${l.id}" aria-label="Hide ${esc(l.title)}"><i data-lucide="eye-off"></i></button>` : ""}
          ${l.moderation === "hidden" ? `<button type="button" class="icon-action" data-unhide="${l.id}" aria-label="Show ${esc(l.title)} again"><i data-lucide="eye"></i></button>` : ""}
          <button type="button" class="icon-action icon-action--danger" data-delete="${l.id}" aria-label="Delete ${esc(l.title)}"><i data-lucide="trash-2"></i></button>
        </div></td>
      </tr>`).join("") || `<tr><td colspan="8" class="hint">${all.length ? "No listings match." : "No listings yet. They'll appear here when students submit items."}</td></tr>`;
    $("#listingCount").textContent = `${list.length} of ${all.length} listings`;
  }
  $("#alSearch").addEventListener("input", (e) => { al.search = e.target.value.trim().toLowerCase(); renderListings(); icons(); });
  $("#alStatus").addEventListener("change", (e) => { al.status = e.target.value; renderListings(); icons(); });
  $("#alType").addEventListener("change", (e) => { al.type = e.target.value; renderListings(); icons(); });
  $("#listingBody").addEventListener("change", (e) => {
    const s = e.target.closest("[data-avail]");
    if (!s) return;
    const l = Store.updateListing(s.dataset.avail, { availability: s.value });
    Store.log("edit", `${l.title}: availability → ${s.value}`); Store.save();
    toast(`Availability updated: ${s.value}.`);
  });
  $("#listingBody").addEventListener("click", (e) => {
    if (e.target.closest("[data-goreview]")) { location.hash = "review"; return; }
    const d = e.target.closest("[data-detail]");
    if (d) return openDetail(Store.getListing(d.dataset.detail));
    const h = e.target.closest("[data-hide]");
    if (h) {
      const l = Store.getListing(h.dataset.hide);
      return confirmAction({ title: "Hide this listing?", text: `“${l.title}” won't be visible to students until you show it again.`, ok: "Hide listing", reason: true }, (why) => {
        Store.moderate(l.id, "hidden", why); renderAll(); toast("Listing hidden. The seller was notified.");
      });
    }
    const u = e.target.closest("[data-unhide]");
    if (u) { const l = Store.getListing(u.dataset.unhide); Store.moderate(l.id, "approved"); renderAll(); toast("Listing is visible again."); return; }
    const del = e.target.closest("[data-delete]");
    if (del) {
      const l = Store.getListing(del.dataset.delete);
      confirmAction({ title: "Delete this listing?", text: `“${l.title}” will be removed permanently. This can't be undone.`, ok: "Delete", reason: true }, (why) => {
        Store.notify(l.sellerId, "rejected", `Your listing “${l.title}” was removed by an admin: ${why}`, "my-listings.html");
        Store.log("delete", `${l.title} — ${why}`);
        Store.deleteListing(l.id);
        renderAll();
        toast("Listing deleted.");
      });
    }
  });

  /* ---------- Scam reports ---------- */
  let rtab = "review";
  const RISK_ORDER = { high: 0, medium: 1, low: 2 };
  const REPORT_TYPE = { payment: "Fake Payment Proof", noshow: "No Show / Ghosting", phishing: "Phishing Link", "fake-item": "Fake / Wrong Item", impersonation: "Impersonation", deposit: "Deposit (DP) Scam", other: "Other" };
  function renderReports() {
    ["review", "verified", "dismissed"].forEach((s) => ($(`[data-rcount="${s}"]`).textContent = Store.reports().filter((r) => r.status === s).length));
    const list = Store.reports().filter((r) => r.status === rtab).sort((a, b) => RISK_ORDER[a.risk] - RISK_ORDER[b.risk] || b.createdAt - a.createdAt);
    $("#reportList").innerHTML = list.map((r) => `
      <li class="rp">
        <div>
          <div class="rv__tags">
            <span class="chip-t ${r.risk === "high" ? "chip-t--warn" : ""}">${r.risk[0].toUpperCase() + r.risk.slice(1)} risk</span>
            <span class="chip-t">${REPORT_TYPE[r.type] || esc(r.type)}</span>
            <span class="chip-t"><i data-lucide="map-pin"></i>${esc(r.place)}</span>
            <span class="chip-t"><i data-lucide="paperclip"></i>${(r.evidence || []).length} evidence</span>
            ${r.seenBy.length ? `<span class="chip-t"><i data-lucide="users"></i>${r.seenBy.length} saw it too</span>` : ""}
          </div>
          <h3 class="rp__title">${esc(r.title)}</h3>
          <p class="rp__text">${esc(r.desc)}</p>
          ${r.ident ? `<span class="rp__ident"><i data-lucide="fingerprint"></i>${esc(r.ident)}</span>` : ""}
          ${(r.evidence || []).length ? `<div class="rp__evidence">${r.evidence.map((src) => `<a href="${src}" target="_blank" rel="noopener"><img src="${src}" alt="Evidence screenshot" /></a>`).join("")}</div>` : ""}
          <p class="hint">Reported ${timeAgo(r.createdAt)} · ${r.anonymous ? "reporter hidden from students" : "reporter: " + esc(Store.getUser(r.reporterId)?.name || "student")}${r.date ? " · happened on " + esc(r.date) : ""}</p>
        </div>
        <div class="rp__actions">
          ${r.status === "review" ? `
            <label for="risk-${r.id}">Risk level</label>
            <select class="mini-select" id="risk-${r.id}" data-risk="${r.id}">
              ${["high", "medium", "low"].map((k) => `<option value="${k}" ${r.risk === k ? "selected" : ""}>${k[0].toUpperCase() + k.slice(1)}</option>`).join("")}
            </select>
            <button type="button" class="btn btn--approve" data-verify="${r.id}"><i data-lucide="badge-check"></i><span>Verify &amp; publish</span></button>
            <button type="button" class="btn btn--outline" data-dismiss="${r.id}"><i data-lucide="x"></i><span>Dismiss</span></button>`
          : `<button type="button" class="btn btn--outline" data-reopen="${r.id}"><i data-lucide="rotate-ccw"></i><span>Move back to review</span></button>`}
        </div>
      </li>`).join("");
    $("#reportEmpty").hidden = list.length > 0;
  }
  $$("[data-rtab]").forEach((t) => t.addEventListener("click", () => {
    rtab = t.dataset.rtab;
    $$("[data-rtab]").forEach((x) => { x.classList.toggle("is-active", x === t); x.setAttribute("aria-selected", String(x === t)); });
    renderReports(); icons();
  }));
  $("#reportList").addEventListener("change", (e) => { const s = e.target.closest("[data-risk]"); if (s) Store.setReportRisk(s.dataset.risk, s.value); });
  $("#reportList").addEventListener("click", (e) => {
    const v = e.target.closest("[data-verify]");
    if (v) { Store.setReportStatus(v.dataset.verify, "verified"); renderAll(); toast("Verified. It now shows on Scam Watch."); return; }
    const d = e.target.closest("[data-dismiss]");
    if (d) return confirmAction({ title: "Dismiss this report?", text: "Use this when it isn't a scam or there's not enough proof.", ok: "Dismiss", reason: true }, (why) => {
      Store.setReportStatus(d.dataset.dismiss, "dismissed", why); renderAll(); toast("Report dismissed.");
    });
    const o = e.target.closest("[data-reopen]");
    if (o) { Store.setReportStatus(o.dataset.reopen, "review"); renderAll(); }
  });

  /* ---------- Users ---------- */
  const us = { search: "", status: "" };
  function renderUsers() {
    const list = students()
      .filter((u) => !us.search || `${u.name} ${u.email} ${u.faculty}`.toLowerCase().includes(us.search))
      .filter((u) => !us.status || (us.status === "flagged" ? reportsAbout(u) > 0 : u.status === us.status))
      .sort((a, b) => reportsAbout(b) - reportsAbout(a) || b.createdAt - a.createdAt);
    $("#userBody").innerHTML = list.map((u) => {
      const n = reportsAbout(u);
      return `
      <tr>
        <td data-label="User"><div class="cell-item">${avatarHTML(u, 40)}<div><strong>${esc(u.name)}</strong><small>${esc(u.email)}</small></div></div></td>
        <td data-label="Faculty">${esc(u.faculty)}</td>
        <td data-label="Joined">${timeAgo(u.createdAt)}</td>
        <td data-label="Listings">${Store.listings().filter((l) => l.sellerId === u.id).length}</td>
        <td data-label="Reports"><span class="report-count${n ? "" : " is-zero"}">${n}</span></td>
        <td data-label="Status"><span class="status status--${u.status}">${u.status === "active" ? "Active" : "Suspended"}</span></td>
        <td data-label="Actions"><div class="row-actions">
          ${u.status === "active"
            ? `<button type="button" class="btn btn--reject btn--sm" data-suspend="${u.id}"><i data-lucide="ban"></i>Suspend</button>`
            : `<button type="button" class="btn btn--outline btn--sm" data-unsuspend="${u.id}"><i data-lucide="rotate-ccw"></i>Restore</button>`}
        </div></td>
      </tr>`;
    }).join("") || `<tr><td colspan="7" class="hint">${students().length ? "No users match." : "No students have registered yet."}</td></tr>`;
  }
  $("#usSearch").addEventListener("input", (e) => { us.search = e.target.value.trim().toLowerCase(); renderUsers(); icons(); });
  $("#usStatus").addEventListener("change", (e) => { us.status = e.target.value; renderUsers(); icons(); });
  $("#userBody").addEventListener("click", (e) => {
    const s = e.target.closest("[data-suspend]");
    if (s) {
      const u = Store.getUser(s.dataset.suspend);
      return confirmAction({ title: `Suspend ${u.name}?`, text: "They won't be able to log in, chat or list items. Their live listings will be hidden.", ok: "Suspend user", reason: true }, (why) => {
        Store.setUserStatus(u.id, "suspended", why); renderAll(); toast(`${u.name} suspended.`);
      });
    }
    const r = e.target.closest("[data-unsuspend]");
    if (r) { const u = Store.getUser(r.dataset.unsuspend); Store.setUserStatus(u.id, "active"); renderAll(); toast(`${u.name} restored.`); }
  });

  /* ---------- Confirm with reason ---------- */
  const confirmDialog = $("#confirmDialog");
  let onConfirm = null;
  function confirmAction({ title, text, ok, reason }, cb) {
    $("#cfTitle").textContent = title;
    $("#cfText").textContent = text;
    $("#cfOk span").textContent = ok;
    $("#cfReasonWrap").hidden = !reason;
    $("#cfReason").value = "";
    $("#cfError").hidden = true;
    onConfirm = cb;
    confirmDialog.showModal();
    if (reason) $("#cfReason").focus();
  }
  $("#confirmForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const why = $("#cfReason").value.trim();
    if (!$("#cfReasonWrap").hidden && why.length < 5) { $("#cfError").textContent = "Write a short reason (5+ characters)."; $("#cfError").hidden = false; return; }
    confirmDialog.close();
    onConfirm && onConfirm(why || "—");
  });

  /* ---------- Render ---------- */
  function renderAll() { renderDashboard(); renderReview(); renderListings(); renderReports(); renderUsers(); icons(); }
  Store.onChange(renderAll);           // new submissions from another tab show up right away
  setInterval(renderAll, 60000);
  route();
});
