document.addEventListener("DOMContentLoaded", () => {
  /* ============ ITEM DETAIL ============ */
  // item.html?id=… — shows one listing from Store.
  const id = new URLSearchParams(location.search).get("id");
  const root = $("#itemRoot");
  const user = me();

  function notFound(msg) {
    root.innerHTML = `<div class="empty-box"><i data-lucide="package-x"></i><strong>${msg}</strong><span>It may have been removed, or it isn't approved yet.</span><a href="marketplace.html" class="btn btn--primary btn--keep"><i data-lucide="shopping-bag"></i><span>Browse Marketplace</span></a></div>`;
    icons();
  }

  function render() {
    const l = Store.getListing(id);
    const isOwner = user && l && l.sellerId === user.id;
    const isAdmin = user && user.role === "admin";
    if (!l || (!Store.isPublic(l) && !isOwner && !isAdmin)) return notFound("This item isn't available.");
    const seller = Store.getUser(l.sellerId);
    document.title = `${l.title} — GaMarketplace`;
    $("#backLink").href = l.type === "auction" ? "bidding.html" : "marketplace.html";

    const photos = l.photos && l.photos.length ? l.photos : [""];
    let price = "";
    if (l.type === "marketplace") price = `${rupiah(l.price)}${l.nego ? " <small>· negotiable</small>" : ""}`;
    if (l.type === "auction") price = `${rupiah(Store.currentPrice(l))} <small>· ${Store.highestBid(l) ? "current bid" : "starting bid"}</small>`;
    if (l.type === "barter") price = l.barter.swap === "open" ? "Open to offers" : `↔ ${esc(l.barter.want)}${l.barter.cash ? ` <small>+ ${rupiah(l.barter.cash)}</small>` : ""}`;
    const badge = l.availability === "sold" ? "SOLD" : l.availability === "reserved" ? "RESERVED" : l.type === "auction" && Store.auctionEnded(l) ? "ENDED" : "";

    let actions = "";
    if (isOwner) {
      actions = `<p class="owner-note"><i data-lucide="info"></i><span>This is your listing${l.moderation !== "approved" ? ` — status: <strong>${l.moderation === "pending" ? "waiting for admin review" : l.moderation}</strong>` : ""}.</span></p>
        <div class="info__actions"><a href="my-listings.html" class="btn btn--primary"><i data-lucide="package"></i><span>Manage in My Listings</span></a></div>`;
    } else if (l.availability === "sold") {
      actions = `<p class="owner-note owner-note--warn"><i data-lucide="circle-alert"></i><span>This item is sold.</span></p>`;
    } else {
      const saved = user && Store.isSaved(user.id, "listing", l.id);
      const main = l.type === "auction"
        ? `<a href="bidding.html#bid-${l.id}" class="btn btn--primary"><i data-lucide="gavel"></i><span>${Store.auctionEnded(l) ? "View auction" : "Place a bid"}</span></a>`
        : `<button type="button" class="btn btn--primary" id="chatBtn"><i data-lucide="message-circle-more"></i><span>Chat seller</span></button>`;
      actions = `<div class="info__actions">
          ${main}
          ${l.type === "auction" ? `<button type="button" class="btn btn--outline" id="chatBtn"><i data-lucide="message-circle-more"></i><span>Chat seller</span></button>` : ""}
          <button type="button" class="btn btn--outline wish-big${saved ? " is-active" : ""}" id="saveBtn" aria-pressed="${!!saved}"><i data-lucide="heart"></i><span>${saved ? "Saved" : "Save"}</span></button>
        </div>
        <a href="scam-watch.html?report=1&about=${encodeURIComponent(l.title)}" class="report-link"><i data-lucide="flag"></i>Report this listing</a>`;
    }

    root.innerHTML = `
      <article class="item">
        <div class="gallery">
          <div class="gallery__main">${photos[0] ? `<img id="mainPhoto" src="${photos[0]}" alt="${esc(l.title)}" />` : photoOrPlaceholder("")}${badge ? `<span class="gallery__badge">${badge}</span>` : ""}</div>
          ${photos.length > 1 ? `<div class="gallery__thumbs">${photos.map((p, i) => `<button type="button" class="${i === 0 ? "is-active" : ""}" data-photo="${i}" aria-label="Photo ${i + 1}"><img src="${p}" alt="" /></button>`).join("")}</div>` : ""}
        </div>
        <div class="info">
          <div class="info__tags">
            <span class="tag-s">${TYPE_LABEL[l.type]}</span>
            <span class="tag-s">${CATEGORY_LABEL[l.category]}</span>
            ${l.type === "auction" && !Store.auctionEnded(l) && l.auction.endsAt ? `<span class="tag-s tag-s--warn">Ends in ${formatLeft(l.auction.endsAt - Date.now())}</span>` : ""}
          </div>
          <h1>${esc(l.title)}</h1>
          <p class="info__price">${price}</p>
          <dl class="facts">
            <div><dt>Condition</dt><dd>${CONDITION_LABEL[l.condition]}</dd></div>
            <div><dt>Meet-up point (tikum)</dt><dd><a href="meetup-map.html#${encodeURIComponent(l.tikum.toLowerCase())}">${esc(l.tikum)}</a></dd></div>
            <div><dt>Posted</dt><dd>${timeAgo(l.approvedAt || l.createdAt)}</dd></div>
            <div><dt>Views</dt><dd>${l.views || 0}</dd></div>
          </dl>
          <p class="info__desc">${esc(l.desc || "No description.")}</p>
          ${actions}
        </div>
      </article>`;

    // Seller panel
    const sp = $("#sellerPanel");
    sp.hidden = false;
    const sellerItems = Store.listings().filter((x) => x.sellerId === l.sellerId);
    sp.innerHTML = `
      <h2 class="panel__title">Seller</h2>
      <div class="seller-card">${avatarHTML(seller, 52)}<div><strong><a href="profile.html?u=${l.sellerId}">${esc(seller?.name || "Student")}</a></strong><span>${seller?.showFaculty === false ? "UGM student" : esc(seller?.faculty || "")} · joined ${seller ? timeAgo(seller.createdAt) : ""}</span></div></div>
      <dl class="seller-stats">
        <div><dt>Live listings</dt><dd>${sellerItems.filter((x) => Store.isPublic(x) && x.availability !== "sold").length}</dd></div>
        <div><dt>Items sold</dt><dd>${sellerItems.filter((x) => x.availability === "sold").length}</dd></div>
      </dl>
      ${seller?.bio ? `<p class="hint">“${esc(seller.bio)}”</p>` : ""}`;
    icons();
  }

  render();
  // count a view once per visit (not for the owner)
  const l0 = Store.getListing(id);
  if (l0 && Store.isPublic(l0) && (!user || user.id !== l0.sellerId)) {
    try { if (!sessionStorage.getItem("gm-viewed-" + id)) { sessionStorage.setItem("gm-viewed-" + id, "1"); Store.addView(id); render(); } } catch {}
  }

  root.addEventListener("click", (e) => {
    const l = Store.getListing(id);
    const t = e.target.closest("[data-photo]");
    if (t) {
      $("#mainPhoto").src = l.photos[Number(t.dataset.photo)];
      $$(".gallery__thumbs button").forEach((b) => b.classList.toggle("is-active", b === t));
      return;
    }
    if (e.target.closest("#chatBtn")) {
      if (!requireLogin("Log in to chat with the seller.")) return;
      location.href = `messages.html?to=${l.sellerId}&listing=${l.id}`;
      return;
    }
    if (e.target.closest("#saveBtn")) {
      if (!requireLogin("Log in to save items.")) return;
      const on = Store.toggleSaved(me().id, "listing", l.id);
      render();
      toast(on ? "Saved to your wishlist" : "Removed from your wishlist");
    }
  });
  Store.onChange(render);
});
