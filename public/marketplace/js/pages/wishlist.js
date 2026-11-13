document.addEventListener("DOMContentLoaded", () => {
  /* ============ WISHLIST ============ */
  // Items, auctions, swaps and requests the logged-in user saved (Store).
  if (!requirePage()) return;
  const KIND = {
    marketplace: { label: "Item", icon: "shopping-bag" },
    auction: { label: "Auction", icon: "gavel" },
    request: { label: "Request", icon: "search" },
  };
  const view = { search: "", sort: "recent", tab: "all" };

  // Turn saved references into cards with the item's CURRENT state
  function savedItems() {
    return (me().wishlist || []).map((w) => {
      if (w.kind === "request") {
        const r = Store.getRequest(w.id);
        if (!r) return null;
        return { w, kind: "request", title: `Looking for: ${r.title}`, price: r.max, sub: "Budget up to", tikum: r.tikum, img: (r.photos || [])[0] || "", href: "looking-for.html", available: !r.found, cta: "I have this" };
      }
      const l = Store.getListing(w.id);
      if (!l || l.type === "barter") return null; // barter was removed
      const kind = l.type;
      const ended = kind === "auction" && Store.auctionEnded(l);
      const price = kind === "barter" ? null : Store.currentPrice(l);
      return {
        w, kind, title: kind === "barter" ? `${l.title}${l.barter.want ? " ↔ " + l.barter.want : ""}` : l.title, price,
        oldPrice: kind === "marketplace" && w.price && w.price > price ? w.price : null,
        sub: kind === "auction" ? `${Store.highestBid(l) ? "Current bid" : "Starting bid"} · ${l.auction.bids.length} bids` : kind === "barter" ? (l.barter.swap === "open" ? "Open to offers" : "Swap") : "",
        tikum: l.tikum, img: listingPhoto(l), href: kind === "auction" ? `bidding.html#bid-${l.id}` : `item.html?id=${l.id}`,
        available: Store.isPublic(l) && l.availability !== "sold" && !ended,
        endingSoon: kind === "auction" && !ended && l.auction.endsAt - Date.now() < 3 * 3600e3,
      };
    }).filter(Boolean);
  }

  function cardHTML(s) {
    const k = KIND[s.kind];
    let flag = "";
    if (!s.available) flag = '<span class="flag flag--gone">No longer available</span>';
    else if (s.oldPrice) flag = `<span class="flag flag--drop">↓ ${Math.round((1 - s.price / s.oldPrice) * 100)}% cheaper</span>`;
    else if (s.endingSoon) flag = '<span class="flag flag--ending">Ending soon</span>';
    return `
      <article class="wl-card${s.available ? "" : " is-unavailable"}">
        <div class="card-media${s.img ? "" : " card-media--text"}">
          <span class="kind"><i data-lucide="${k.icon}"></i>${k.label}</span>
          <button class="wish-btn" type="button" data-remove="${s.w.kind}:${s.w.id}" aria-label="Remove ${esc(s.title)} from wishlist"><i data-lucide="heart"></i></button>
          ${flag}
          ${s.img ? `<img src="${s.img}" alt="" loading="lazy" />` : `<i data-lucide="${k.icon}"></i>`}
        </div>
        <div class="card-body">
          <h3 class="card-title"><a href="${s.href}">${esc(s.title)}</a></h3>
          ${s.sub ? `<span class="sub">${esc(s.sub)}</span>` : ""}
          <strong class="price">${s.price === null ? "Barter" : rupiah(s.price)}${s.oldPrice ? `<span class="old-price">${rupiah(s.oldPrice)}</span>` : ""}</strong>
          <div class="wl-card__foot"><span><i data-lucide="map-pin"></i>${esc(s.tikum)}</span><span>Saved ${timeAgo(s.w.at)}</span></div>
        </div>
      </article>`;
  }

  function render() {
    const all = savedItems();
    const base = all.filter((s) => !view.search || s.title.toLowerCase().includes(view.search));
    const tabKind = (s) => (s.kind === "marketplace" ? "item" : s.kind);
    ["all", "item", "auction", "request"].forEach((t) => ($(`[data-count="${t}"]`).textContent = t === "all" ? base.length : base.filter((s) => tabKind(s) === t).length));
    const key = {
      recent: (s) => -s.w.at,
      "price-asc": (s) => s.price ?? Infinity,
      "price-desc": (s) => -(s.price ?? -1),
      drop: (s) => (s.oldPrice ? -(1 - s.price / s.oldPrice) : 0),
    }[view.sort];
    const list = base.filter((s) => view.tab === "all" || tabKind(s) === view.tab).sort((a, b) => (b.available - a.available) || key(a) - key(b));
    $("#wlGrid").innerHTML = list.map(cardHTML).join("");
    $("#emptyState").hidden = list.length > 0;
    $("#emptyTitle").textContent = all.length ? "Nothing saved here yet." : "Your wishlist is empty.";
    $("#clearUnavailable").disabled = !all.some((s) => !s.available);
    icons();
  }

  $("#wlSearch").addEventListener("input", (e) => { view.search = e.target.value.trim().toLowerCase(); render(); });
  $("#sortSelect").addEventListener("change", (e) => { view.sort = e.target.value; render(); });
  $$(".status-tab").forEach((t) => t.addEventListener("click", () => {
    view.tab = t.dataset.tab;
    $$(".status-tab").forEach((x) => { x.classList.toggle("is-active", x === t); x.setAttribute("aria-selected", String(x === t)); });
    render();
  }));
  $("#wlGrid").addEventListener("click", (e) => {
    const btn = e.target.closest("[data-remove]");
    if (!btn) return;
    const [kind, id] = btn.dataset.remove.split(":");
    btn.closest(".wl-card").classList.add("is-removing");
    setTimeout(() => {
      Store.toggleSaved(me().id, kind, id);
      render();
      toast("Removed from wishlist.", { label: "Undo", run: () => { Store.toggleSaved(me().id, kind, id); render(); } });
    }, 180);
  });
  $("#clearUnavailable").addEventListener("click", () => {
    const gone = savedItems().filter((s) => !s.available);
    gone.forEach((s) => Store.toggleSaved(me().id, s.w.kind, s.w.id));
    render();
    toast(`Removed ${gone.length} unavailable item${gone.length === 1 ? "" : "s"}.`);
  });
  render();
  Store.onChange(render);
});
