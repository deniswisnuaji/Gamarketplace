document.addEventListener("DOMContentLoaded", () => {
    /* ============ SPLASH SCREEN ============ */
  const splash = document.getElementById("splash");
  if (splash) {
    const bar = document.getElementById("splashBar");
    const text = document.getElementById("splashText");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const MIN_SHOW = reduceMotion ? 300 : 1500; // logo stays at least this long (ms)
    const MAX_SHOW = 8000;                      // never longer than this (ms)
    const start = performance.now();
    let shown = 0;   // percent currently displayed
    let done = false;

    const setProgress = (pct) => {
      pct = Math.max(shown, Math.min(100, Math.round(pct)));
      shown = pct;
      bar.style.width = pct + "%";
      text.textContent = pct >= 100 ? "Ready" : "Loading… " + pct + "%";
    };

    // Real progress: count how many page images have finished loading
    const images = [...document.images].filter((img) => !img.closest(".splash"));
    let loaded = 0;
    const onAsset = () => {
      loaded++;
      // images fill up to 90%; the last 10% is the full page "load" event
      setProgress(10 + (loaded / Math.max(images.length, 1)) * 80);
    };
    images.forEach((img) => {
      if (img.complete) onAsset();
      else {
        img.addEventListener("load", onAsset, { once: true });
        img.addEventListener("error", onAsset, { once: true });
      }
    });
    setProgress(10);

    const finish = () => {
      if (done) return;
      done = true;
      setProgress(100);
      const wait = Math.max(0, MIN_SHOW - (performance.now() - start));
      setTimeout(() => {
        splash.classList.add("is-hidden");
        document.body.classList.remove("splash-active");
        splash.addEventListener("transitionend", () => splash.remove(), { once: true });
        setTimeout(() => splash.remove(), 1200); // fallback
      }, wait + 350); // small pause so the full bar is visible
    };

    if (document.readyState === "complete") finish();
    else window.addEventListener("load", finish, { once: true });
    setTimeout(finish, MAX_SHOW);
  }

  // Render Lucide icons


  /* ============ HOME PAGE CONTENT ============ */
  // Everything below comes from Store: real auctions, listings and verified scam reports.
  const ENDING_TAG_MS = 30 * 60e3;

  function auctionCard(a) {
    const u = me();
    const saved = u && Store.isSaved(u.id, "listing", a.id);
    const left = a.auction.endsAt - Date.now();
    const tag = left < ENDING_TAG_MS ? '<span class="tag tag--ending">Ending Soon</span>' : '<span class="tag tag--live">LIVE</span>';
    return `
      <article class="auction-card" data-id="${a.id}">
        <div class="card-media">
          ${tag}
          <button class="wish-btn${saved ? " is-active" : ""}" type="button" data-wish="${a.id}" aria-label="Save ${esc(a.title)}" aria-pressed="${!!saved}"><i data-lucide="heart"></i></button>
          ${photoOrPlaceholder(listingPhoto(a), a.title)}
        </div>
        <div class="card-body">
          <h3 class="card-title"><a href="bidding.html#bid-${a.id}">${esc(a.title)}</a></h3>
          <div class="auction-meta">
            <div><span class="meta-label">${Store.highestBid(a) ? "Current bid" : "Starting at"}</span><strong class="price">${rupiah(Store.currentPrice(a))}</strong></div>
            <div class="timer"><span class="meta-label"><i data-lucide="clock"></i> Ends in</span><strong class="timer__value" data-end="${a.auction.endsAt}">${formatLeft(left)}</strong></div>
          </div>
        </div>
      </article>`;
  }
  function listingCard(l) {
    const u = me();
    const saved = u && Store.isSaved(u.id, "listing", l.id);
    return `
      <article class="listing-card" data-category="${l.category}">
        <div class="card-media">
          <button class="wish-btn${saved ? " is-active" : ""}" type="button" data-wish="${l.id}" aria-label="Save ${esc(l.title)}" aria-pressed="${!!saved}"><i data-lucide="heart"></i></button>
          ${photoOrPlaceholder(listingPhoto(l), l.title)}
        </div>
        <div class="card-body">
          <h3 class="card-title"><a href="item.html?id=${l.id}">${esc(l.title)}</a></h3>
          <strong class="price">${rupiah(l.price)}</strong>
          <span class="location"><i data-lucide="map-pin"></i> ${esc(l.tikum)}</span>
        </div>
      </article>`;
  }
  const emptyBox = (icon, title, text, href, label) => `<div class="empty-box"><i data-lucide="${icon}"></i><strong>${title}</strong><span>${text}</span>${href ? `<a href="${href}" class="btn btn--primary btn--keep"><i data-lucide="plus"></i><span>${label}</span></a>` : ""}</div>`;

  let chip = "all";
  function render() {
    const user = me();
    // Account card
    $("#accountTitle").textContent = user ? user.name : "Join GaMarketplace";
    $("#accountSub").textContent = user ? `${user.faculty ? user.faculty + " · " : ""}signed in with UGM email` : "Log in or create an account with your UGM email";
    $("#accountCard").href = user ? "profile.html" : "login.html";

    // Live auctions (ending soonest first)
    const live = Store.publicListings("auction").filter((a) => a.auction.endsAt && !Store.auctionEnded(a)).sort((a, b) => a.auction.endsAt - b.auction.endsAt).slice(0, 4);
    $("#auctionGrid").innerHTML = live.length ? live.map(auctionCard).join("")
      : emptyBox("gavel", "No live auctions yet", "List an item as an auction and it will show here after admin approval.", "list-item.html", "Start an Auction");

    // Recent listings (newest approved, not sold)
    const all = Store.publicListings("marketplace").filter((l) => l.availability !== "sold").sort((a, b) => (b.approvedAt || 0) - (a.approvedAt || 0));
    const list = (chip === "all" ? all : all.filter((l) => l.category === chip)).slice(0, 10);
    $("#listingGrid").innerHTML = list.map(listingCard).join("");
    const empty = $("#emptyState");
    empty.hidden = list.length > 0;
    empty.innerHTML = all.length ? `No listings in this category yet. <a href="list-item.html">List the first one</a>.` : emptyBox("shopping-bag", "No listings yet", "Be the first to sell something to other UGM students.", "list-item.html", "List an Item");

    // Scam bulletin: newest verified reports
    const alerts = Store.reports().filter((r) => r.status === "verified").sort((a, b) => b.verifiedAt - a.verifiedAt).slice(0, 2);
    $("#bulletinList").innerHTML = alerts.map((r) => `
      <li>
        <a href="scam-watch.html#alert-${r.id}" class="alert alert--danger">
          <i data-lucide="triangle-alert"></i>
          <div>
            <div class="alert__top"><strong>${esc(r.title)}</strong><time>${timeAgo(r.verifiedAt)}</time></div>
            <p>${esc(r.desc.length > 110 ? r.desc.slice(0, 110) + "…" : r.desc)}</p>
          </div>
        </a>
      </li>`).join("") + `
      <li>
        <a href="scam-watch.html#checklist" class="alert alert--info">
          <i data-lucide="info"></i>
          <div><strong>How to Trade Safely</strong><p>${alerts.length ? "Tips for a safe and smooth transaction on GaMarketplace." : "No verified scam reports yet. Read the safety checklist before you meet."}</p></div>
          <i data-lucide="chevron-right" class="alert__arrow"></i>
        </a>
      </li>`;
    icons();
  }

  // Countdown timers
  setInterval(() => {
    let ended = false;
    $$("[data-end]").forEach((el) => {
      const left = Number(el.dataset.end) - Date.now();
      el.textContent = formatLeft(left);
      if (left <= 0) ended = true;
    });
    if (ended) { Store.finalizeAuctions(); render(); }
  }, 1000);

  // Category chips
  $$(".chip[data-filter]").forEach((c) => c.addEventListener("click", () => {
    chip = c.dataset.filter;
    $$(".chip[data-filter]").forEach((x) => { x.classList.toggle("is-active", x === c); x.setAttribute("aria-pressed", String(x === c)); });
    render();
  }));

  // Wishlist hearts
  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-wish]");
    if (!btn || !requireLogin("Log in to save items.")) return;
    const l = Store.getListing(btn.dataset.wish);
    if (l.sellerId === me().id) return toast("That's your own item.");
    const on = Store.toggleSaved(me().id, "listing", l.id);
    btn.classList.toggle("is-active", on);
    btn.setAttribute("aria-pressed", String(on));
    toast(on ? "Saved to your wishlist" : "Removed from your wishlist");
  });

  render();
  Store.onChange(render);
});
