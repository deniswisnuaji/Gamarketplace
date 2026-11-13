document.addEventListener("DOMContentLoaded", () => {
  /* ============ BIDDING ARENA ============ */
  // Auctions are approved "Auction" listings from Store. Bids are real bids placed by students.
  const view = { search: "", area: "", category: "", condition: "", priceMin: null, priceMax: null, faculty: "", type: "", tab: "all", sort: "ending", dir: "asc", mineOnly: false, activityAll: false };
  const ENDING_SOON_MS = 3 * 3600e3, ENDING_TAG_MS = 30 * 60e3;

  const auctions = () => Store.publicListings("auction").filter((a) => a.auction.endsAt);
  const left = (a) => a.auction.endsAt - Date.now();
  const isEnded = (a) => Store.auctionEnded(a);
  const isNew = (a) => Date.now() - (a.approvedAt || a.createdAt) < 12 * 3600e3;
  const bidCount = (a) => a.auction.bids.length;
  const myBidOn = (a) => { const u = me(); return u ? a.auction.bids.filter((b) => b.userId === u.id).pop() : null; };
  const leaderId = (a) => Store.highestBid(a)?.userId;
  const status = (a) => (isEnded(a) ? "ended" : left(a) < ENDING_TAG_MS ? "ending" : isNew(a) ? "new" : "live");

  function passes(a) {
    const s = Store.getUser(a.sellerId) || {};
    if (view.search && !`${a.title} ${a.desc}`.toLowerCase().includes(view.search)) return false;
    if (view.area && a.tikum !== view.area) return false;
    if (view.category && a.category !== view.category) return false;
    if (view.condition && a.condition !== view.condition) return false;
    if (view.faculty && s.faculty !== view.faculty) return false;
    if (view.type === "buy-now" && !a.auction.buyNow) return false;
    if (view.type === "standard" && a.auction.buyNow) return false;
    const price = Store.currentPrice(a);
    if (view.priceMin !== null && price < view.priceMin) return false;
    if (view.priceMax !== null && price > view.priceMax) return false;
    if (view.mineOnly && !myBidOn(a)) return false;
    return true;
  }
  function inTab(a, tab) {
    if (tab === "live") return !isEnded(a) && !isNew(a);
    if (tab === "ending") return !isEnded(a) && left(a) < ENDING_SOON_MS;
    if (tab === "new") return !isEnded(a) && isNew(a);
    return true;
  }
  function sortList(list) {
    const d = view.tab === "most" ? -1 : view.dir === "asc" ? 1 : -1;
    const key = { ending: (a) => (isEnded(a) ? Infinity : left(a)), bids: bidCount, price: Store.currentPrice, newest: (a) => -(a.approvedAt || 0) }[view.tab === "most" ? "bids" : view.sort];
    return list.sort((x, y) => (isEnded(x) !== isEnded(y) ? (isEnded(x) ? 1 : -1) : (key(x) - key(y)) * d));
  }

  function tagHTML(a) {
    const s = status(a);
    if (s === "ended") return '<span class="tag tag--ended">Ended</span>';
    if (s === "ending") return '<span class="tag tag--ending">Ending Soon</span>';
    if (s === "new") return '<span class="tag tag--new">New Auction</span>';
    return '<span class="tag tag--live">LIVE <i data-lucide="radio"></i></span>';
  }
  function myStatusHTML(a) {
    const u = me();
    if (!u || !myBidOn(a)) return "";
    if (isEnded(a)) return leaderId(a) === u.id ? '<span class="my-status my-status--won">You won</span>' : "";
    return leaderId(a) === u.id ? '<span class="my-status my-status--leading">You\'re leading</span>' : '<span class="my-status my-status--outbid">Outbid</span>';
  }
  function cardHTML(a) {
    const u = me();
    const watched = u && Store.isSaved(u.id, "listing", a.id);
    return `
      <article class="bid-card${isEnded(a) ? " is-ended" : ""}" data-id="${a.id}" data-status="${status(a)}">
        <div class="card-media">
          ${tagHTML(a)}
          <button class="wish-btn${watched ? " is-active" : ""}" type="button" data-watch="${a.id}" aria-label="Watch ${esc(a.title)}" aria-pressed="${!!watched}"><i data-lucide="heart"></i></button>
          ${myStatusHTML(a)}
          ${photoOrPlaceholder(listingPhoto(a), a.title)}
        </div>
        <div class="card-body">
          <button type="button" class="card-open" data-open="${a.id}">${esc(a.title)}</button>
          <p class="card-desc">${esc(a.desc)}</p>
          <div class="card-info">
            <span title="Meet-up point"><i data-lucide="map-pin"></i>${esc(a.tikum)}</span>
            <span><i data-lucide="user-round"></i>${bidCount(a)} bid${bidCount(a) === 1 ? "" : "s"}</span>
          </div>
          <div class="bid-row">
            <div class="bid-box"><span>${isEnded(a) ? "Final Bid" : bidCount(a) ? "Current Bid" : "Starting Bid"}</span><strong>${rupiah(Store.currentPrice(a))}</strong></div>
            <div class="bid-box bid-box--time"><span><i data-lucide="clock"></i> ${isEnded(a) ? "Status" : "Ends in"}</span><strong data-timer>${formatLeft(left(a))}</strong></div>
          </div>
        </div>
      </article>`;
  }

  function render() {
    const all = auctions();
    const base = all.filter(passes);
    $('[data-tab-count="all"]').textContent = base.length;
    $('[data-tab-count="live"]').textContent = base.filter((a) => inTab(a, "live")).length;
    $('[data-tab-count="ending"]').textContent = base.filter((a) => inTab(a, "ending")).length;
    const list = sortList(base.filter((a) => inTab(a, view.tab)));
    $("#bidGrid").innerHTML = list.map(cardHTML).join("");
    const empty = $("#emptyState");
    empty.hidden = list.length > 0;
    empty.innerHTML = all.length
      ? `<p><strong>No auctions match these filters.</strong></p><p>Try another category or reset the filters.</p><button type="button" class="btn btn--outline" id="emptyResetBtn">Reset filters</button>`
      : `<p><strong>No auctions yet.</strong></p><p>List an item as an Auction and it will appear here after an admin approves it.</p><a href="list-item.html" class="btn btn--primary btn--keep"><i data-lucide="gavel"></i><span>Start an Auction</span></a>`;
    $("#emptyResetBtn")?.addEventListener("click", resetFilters);
    $("#mineBanner").hidden = !view.mineOnly;
    renderStats();
    renderActivity();
    icons();
  }

  function renderStats() {
    const u = me();
    const all = Store.listings().filter((l) => l.type === "auction");
    const mineBid = u ? all.filter((a) => a.auction.bids.some((b) => b.userId === u.id)) : [];
    $("#statActive").textContent = mineBid.filter((a) => !isEnded(a)).length;
    $("#statWon").textContent = mineBid.filter((a) => isEnded(a) && leaderId(a) === u.id).length;
    $("#statWatching").textContent = u ? (u.wishlist || []).filter((w) => all.some((a) => a.id === w.id)).length : 0;
  }

  // Recent bids across all public auctions
  function renderActivity() {
    const events = [];
    Store.publicListings("auction").forEach((a) => {
      a.auction.bids.forEach((b) => events.push({ at: b.at, html: `<strong>${esc(Store.getUser(b.userId)?.name || "A student")}</strong> ${b.buyNow ? "bought now" : "placed a bid"} <strong>${rupiah(b.amount)}</strong> on ${esc(a.title)}`, icon: b.buyNow ? "shopping-bag" : "gavel" }));
      if (isEnded(a) && Store.highestBid(a)) events.push({ at: a.auction.endsAt, html: `<strong>${esc(Store.getUser(leaderId(a))?.name || "A student")}</strong> won the auction <strong>${esc(a.title)}</strong>`, icon: "trophy", won: true });
    });
    events.sort((x, y) => y.at - x.at);
    const shown = events.slice(0, view.activityAll ? 15 : 4);
    $("#activityList").innerHTML = shown.length ? shown.map((e) => `
      <li><span class="activity__icon ${e.won ? "activity__icon--won" : ""}"><i data-lucide="${e.icon}"></i></span><p class="activity__text">${e.html}</p><time>${timeAgo(e.at)}</time></li>`).join("")
      : '<li class="hint">No bids yet.</li>';
    $("#activityMoreBtn").hidden = events.length <= 4;
  }

  /* ---- Timers (every second, without re-rendering everything) ---- */
  function tick() {
    let changed = false;
    $$(".bid-card").forEach((card) => {
      const a = Store.getListing(card.dataset.id);
      if (!a) return;
      const t = card.querySelector("[data-timer]");
      if (t) t.textContent = formatLeft(left(a));
      if (card.dataset.status !== status(a)) changed = true;
    });
    if (changed) { Store.finalizeAuctions(); render(); }
    updateDialog();
  }

  /* ---- Bid dialog ---- */
  const dialog = $("#bidDialog");
  let openId = null;
  function openDialog(id) {
    const a = Store.getListing(id);
    if (!a) return;
    openId = id;
    const s = Store.getUser(a.sellerId);
    const img = $("#bdImg");
    img.src = listingPhoto(a) || ""; img.alt = a.title; img.hidden = !listingPhoto(a);
    $("#bidDialogTitle").textContent = a.title;
    $("#bdDesc").innerHTML = `${esc(a.desc)} <a class="text-link" href="item.html?id=${a.id}">View item page</a>`;
    $("#bdFaculty").textContent = `Meet-up: ${a.tikum}`;
    $("#bdSeller").textContent = s ? s.name : "Student";
    $("#bidError").hidden = true;
    $("#bidAmount").value = Store.minNextBid(a);
    updateDialog(true);
    dialog.showModal();
    if (!isEnded(a)) $("#bidAmount").select();
  }
  function updateDialog(full = false) {
    if (!dialog.open && !full) return;
    const a = Store.getListing(openId);
    if (!a) return;
    const u = me();
    const ended = isEnded(a), own = u && a.sellerId === u.id;
    $("#bdCurrent").textContent = rupiah(Store.currentPrice(a));
    $("#bdBids").textContent = bidCount(a);
    $("#bdEnds").textContent = formatLeft(left(a));
    const min = Store.minNextBid(a);
    $("#bdMinHint").textContent = ended || own ? "" : `(minimum ${rupiah(min)})`;
    $("#bidAmount").min = min;
    $("#bidAmount").step = Store.increment(Store.currentPrice(a));
    $("#bidAmount").disabled = ended || own;
    $("#placeBidBtn").disabled = ended || own;
    const st = $("#bdStatus");
    st.className = "bid-status";
    const mine = myBidOn(a);
    let msg = "", cls = "";
    if (own) { msg = "This is your auction. You can't bid on it."; cls = "ended"; }
    else if (ended && u && leaderId(a) === u.id) { msg = "🎉 You won this auction. Chat the seller to arrange the meet-up."; cls = "leading"; }
    else if (ended) { msg = "This auction has ended."; cls = "ended"; }
    else if (mine && leaderId(a) === u.id) { msg = `You're the highest bidder with ${rupiah(mine.amount)}.`; cls = "leading"; }
    else if (mine) { msg = `You've been outbid. Your last bid was ${rupiah(mine.amount)}.`; cls = "outbid"; }
    st.hidden = !msg;
    if (msg) { st.textContent = msg; st.classList.add(`bid-status--${cls}`); }
    if (full || $("#quickBids").dataset.forPrice !== String(Store.currentPrice(a))) {
      const inc = Store.increment(Store.currentPrice(a));
      $("#quickBids").dataset.forPrice = Store.currentPrice(a);
      $("#quickBids").innerHTML = ended || own ? "" : [0, 1, 4].map((n) => `<button type="button" data-quick="${min + inc * n}">${rupiah(min + inc * n)}</button>`).join("");
    }
    const buy = $("#buyNowBtn");
    buy.hidden = !a.auction.buyNow || ended || own || Store.currentPrice(a) >= a.auction.buyNow;
    if (!buy.hidden) buy.textContent = `Buy Now ${rupiah(a.auction.buyNow)}`;
  }

  $("#bidForm").addEventListener("submit", (e) => {
    e.preventDefault();
    if (!requireLogin("Log in to place a bid.")) return;
    const a = Store.getListing(openId);
    const res = Store.placeBid(a.id, Number($("#bidAmount").value));
    if (res.error) { $("#bidError").textContent = res.error; $("#bidError").hidden = false; return; }
    dialog.close();
    render();
    toast(`Bid placed: ${rupiah(Number($("#bidAmount").value))} on ${a.title}.`);
  });
  $("#quickBids").addEventListener("click", (e) => { const b = e.target.closest("[data-quick]"); if (b) { $("#bidAmount").value = b.dataset.quick; $("#bidError").hidden = true; } });
  $("#buyNowBtn").addEventListener("click", () => {
    if (!requireLogin("Log in to buy.")) return;
    const a = Store.getListing(openId);
    if (!confirm(`Buy ${a.title} now for ${rupiah(a.auction.buyNow)}? This ends the auction.`)) return;
    const res = Store.buyNow(a.id);
    if (res.error) return toast(res.error);
    dialog.close();
    render();
    toast(`🎉 You bought ${a.title}. Chat the seller to arrange the meet-up.`);
  });
  $("#bidDialogClose").addEventListener("click", () => dialog.close());
  dialog.addEventListener("close", () => { openId = null; history.replaceState(null, "", location.pathname + location.search); });

  /* ---- Filters / tabs ---- */
  function countActiveFilters() {
    const n = ["category", "condition", "faculty", "type"].filter((k) => view[k]).length + (view.priceMin !== null || view.priceMax !== null ? 1 : 0);
    $("#filterCount").textContent = n;
    $("#filterCount").hidden = n === 0;
    $$("[data-filter]").forEach((sel) => sel.closest(".pill-select").classList.toggle("is-set", !!sel.value));
  }
  function setTab(tab) {
    view.tab = tab;
    $$(".status-tab").forEach((t) => { const on = t.dataset.tab === tab; t.classList.toggle("is-active", on); t.setAttribute("aria-selected", String(on)); });
  }
  const pricePop = setupPricePopover({
    emptyText: "Price Range",
    getRange: () => ({ min: view.priceMin, max: view.priceMax }),
    onApply: (min, max) => { view.priceMin = min; view.priceMax = max; countActiveFilters(); render(); },
  });
  function resetFilters() {
    Object.assign(view, { search: "", area: "", category: "", condition: "", priceMin: null, priceMax: null, faculty: "", type: "", mineOnly: false });
    $("#auctionSearch").value = "";
    $("#areaSelect").value = "";
    $$("[data-filter]").forEach((s) => (s.value = ""));
    $("#viewMyBidsBtn span").textContent = "View My Bids";
    pricePop.sync();
    countActiveFilters();
    render();
  }
  $("#auctionSearch").addEventListener("input", (e) => { view.search = e.target.value.trim().toLowerCase(); render(); });
  $("#areaSelect").addEventListener("change", (e) => { view.area = e.target.value; render(); });
  function syncSortDir() { const b = $("#sortDir"); b.dataset.dir = view.dir; b.setAttribute("aria-label", `Sort direction: ${view.dir === "asc" ? "ascending" : "descending"}`); }
  $("#sortSelect").addEventListener("change", (e) => {
    view.sort = e.target.value;
    view.dir = e.target.value === "ending" || e.target.value === "newest" ? "asc" : "desc";
    syncSortDir();
    if (view.tab === "most") setTab("all");
    render();
  });
  $("#sortDir").addEventListener("click", () => { view.dir = view.dir === "asc" ? "desc" : "asc"; syncSortDir(); render(); });
  $$("[data-filter]").forEach((sel) => sel.addEventListener("change", () => { view[sel.dataset.filter] = sel.value; countActiveFilters(); render(); }));
  $("#resetBtn").addEventListener("click", resetFilters);
  $("#filterToggle").addEventListener("click", (e) => {
    const open = e.currentTarget.getAttribute("aria-expanded") !== "true";
    e.currentTarget.setAttribute("aria-expanded", String(open));
    $("#filterSelects").hidden = !open;
  });
  if (window.matchMedia("(max-width: 760px)").matches) { $("#filterToggle").setAttribute("aria-expanded", "false"); $("#filterSelects").hidden = true; }
  $$(".status-tab").forEach((t) => t.addEventListener("click", () => { setTab(t.dataset.tab); render(); }));

  $("#bidGrid").addEventListener("click", (e) => {
    const w = e.target.closest("[data-watch]");
    if (w) {
      if (!requireLogin("Log in to watch auctions.")) return;
      const a = Store.getListing(w.dataset.watch);
      if (a.sellerId === me().id) return toast("That's your own auction.");
      const on = Store.toggleSaved(me().id, "listing", a.id);
      w.classList.toggle("is-active", on); w.setAttribute("aria-pressed", String(on));
      renderStats();
      toast(on ? "Added to your watchlist" : "Removed from your watchlist");
      return;
    }
    const o = e.target.closest("[data-open]");
    if (o) openDialog(o.dataset.open);
  });
  function toggleMine(on) {
    if (on && !requireLogin("Log in to see your bids.")) return;
    view.mineOnly = on;
    $("#viewMyBidsBtn span").textContent = on ? "Show All Auctions" : "View My Bids";
    if (on) setTab("all");
    render();
    if (on) $("#bidGrid").scrollIntoView({ behavior: "smooth", block: "start" });
  }
  $("#viewMyBidsBtn").addEventListener("click", () => toggleMine(!view.mineOnly));
  $("#mineOffBtn").addEventListener("click", () => toggleMine(false));
  $("#activityMoreBtn").addEventListener("click", (e) => {
    view.activityAll = !view.activityAll;
    e.currentTarget.setAttribute("aria-expanded", String(view.activityAll));
    e.currentTarget.querySelector("span").textContent = view.activityAll ? "Show Less" : "See All Activity";
    renderActivity(); icons();
  });

  /* ---- Start ---- */
  syncSortDir();
  countActiveFilters();
  render();
  setInterval(tick, 1000);
  Store.onChange(render);
  const hashBid = location.hash.match(/^#bid-(.+)$/);
  if (hashBid && Store.getListing(hashBid[1])) openDialog(hashBid[1]);
});
