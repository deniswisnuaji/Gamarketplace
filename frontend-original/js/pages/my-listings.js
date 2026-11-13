document.addEventListener("DOMContentLoaded", () => {
  /* ============ MY LISTINGS ============ */
  // Shows the logged-in user's own listings from Store.
  // moderation = pending | approved | rejected | hidden (admin)   availability = available | reserved | sold (seller)
  if (!requirePage()) return;
  const user = me();
  const MOD_TAG = {
    approved: '<span class="tag tag--approved"><i data-lucide="circle-check"></i>Live</span>',
    pending: '<span class="tag tag--pending"><i data-lucide="hourglass"></i>In review</span>',
    rejected: '<span class="tag tag--rejected"><i data-lucide="circle-x"></i>Rejected</span>',
    hidden: '<span class="tag tag--rejected"><i data-lucide="eye-off"></i>Hidden by admin</span>',
  };
  const view = { search: "", type: "", sort: "newest", tab: "all" };
  const mine = () => Store.listings().filter((l) => l.sellerId === user.id);
  const tabOf = (l) => (l.availability === "sold" ? "sold" : l.moderation === "approved" ? "live" : l.moderation === "hidden" ? "rejected" : l.moderation);

  function priceText(l) {
    if (l.type === "barter") return l.barter.swap === "open" ? "Open to offers" : `↔ ${esc(l.barter.want)}${l.barter.cash ? " + " + rupiah(l.barter.cash) : ""}`;
    if (l.type === "auction") return Store.highestBid(l) ? `Current bid ${rupiah(Store.currentPrice(l))}` : `Starts at ${rupiah(l.price)}`;
    return rupiah(l.price) + (l.nego ? " (nego)" : "");
  }

  function extraHTML(l) {
    // Auction status
    if (l.type === "auction" && l.moderation === "approved") {
      const bids = l.auction.bids.length;
      const top = Store.highestBid(l);
      const ended = Store.auctionEnded(l);
      const status = ended ? (top ? `Ended — winner: <strong>${esc(Store.getUser(top.userId)?.name || "student")}</strong> (${rupiah(top.amount)})` : "Ended with no bids")
        : `${bids} bid${bids === 1 ? "" : "s"} · ends in ${formatLeft(l.auction.endsAt - Date.now())}`;
      const chat = ended && top ? ` <a class="text-link" href="messages.html?to=${top.userId}&listing=${l.id}">Chat winner</a>` : "";
      return `<p class="ml-item__extra"><i data-lucide="gavel"></i><span>${status}${chat}</span></p>`;
    }
    // Barter offers the seller can accept / decline
    if (l.type === "barter" && l.barter.offers.length) {
      return `<div class="offers"><p class="offers__title">Offers (${l.barter.offers.length})</p>${l.barter.offers.map((o) => {
        const u = Store.getUser(o.userId);
        return `<div class="offer">
          ${avatarHTML(u, 30)}
          <div class="offer__text"><strong>${esc(u?.name || "Student")}</strong> offers <strong>${esc(o.itemTitle)}</strong>${o.cash ? " + " + rupiah(o.cash) : ""}${o.msg ? `<br /><span class="hint">“${esc(o.msg)}”</span>` : ""}</div>
          ${o.status === "sent" && l.availability !== "sold" ? `
            <button type="button" class="btn btn--primary btn--sm btn--keep" data-accept="${l.id}:${o.id}"><span>Accept</span></button>
            <button type="button" class="btn btn--outline btn--sm" data-decline="${l.id}:${o.id}">Decline</button>`
            : `<span class="tag ${o.status === "accepted" ? "tag--approved" : "tag--type"}">${o.status === "accepted" ? "Accepted" : o.status === "declined" ? "Declined" : "Sent"}</span>`}
          <a class="btn btn--outline btn--sm" href="messages.html?to=${o.userId}&listing=${l.id}" aria-label="Chat ${esc(u?.name || "")}"><i data-lucide="message-circle-more"></i></a>
        </div>`;
      }).join("")}</div>`;
    }
    return "";
  }

  function itemHTML(l) {
    const soldTag = l.availability === "sold" ? '<span class="tag tag--sold">Sold</span>' : "";
    let controls = "";
    if (l.moderation === "approved" && l.availability !== "sold" && l.type !== "auction") {
      controls = `
        <label class="sr-only" for="avail-${l.id}">Availability</label>
        <select class="avail-select" id="avail-${l.id}" data-avail="${l.id}">
          <option value="available"${l.availability === "available" ? " selected" : ""}>Available</option>
          <option value="reserved"${l.availability === "reserved" ? " selected" : ""}>Reserved</option>
          <option value="sold">Mark as sold</option>
        </select>`;
    } else if (l.availability === "sold" && l.type !== "auction") {
      controls = `<button type="button" class="btn btn--outline btn--sm" data-relist="${l.id}"><i data-lucide="rotate-ccw"></i>Relist</button>`;
    } else if (l.moderation === "rejected") {
      controls = `<button type="button" class="btn btn--primary btn--sm btn--keep" data-edit="${l.id}"><i data-lucide="pencil"></i><span>Fix &amp; resubmit</span></button>`;
    }
    const canEdit = l.moderation !== "rejected" && !(l.type === "auction" && l.moderation === "approved");
    return `
      <li class="ml-item${l.availability === "sold" ? " is-sold" : ""}">
        <a class="ml-item__img" href="item.html?id=${l.id}">${photoOrPlaceholder(listingPhoto(l))}</a>
        <div class="ml-item__main">
          <div class="ml-item__tags">${l.availability === "sold" ? "" : MOD_TAG[l.moderation]}<span class="tag tag--type">${TYPE_LABEL[l.type]}</span>${soldTag}${l.availability === "reserved" ? '<span class="tag tag--pending">Reserved</span>' : ""}</div>
          <h3 class="ml-item__title"><a href="item.html?id=${l.id}">${esc(l.title)}</a></h3>
          <strong class="price">${priceText(l)}</strong>
          <div class="ml-item__meta">
            <span><i data-lucide="map-pin"></i>${esc(l.tikum)}</span>
            <span><i data-lucide="eye"></i>${l.views || 0} views</span>
            <span><i data-lucide="heart"></i>${Store.users().filter((u) => (u.wishlist || []).some((w) => w.id === l.id)).length} saves</span>
            <span><i data-lucide="clock"></i>${timeAgo(l.createdAt)}</span>
          </div>
          ${l.moderation === "rejected" || l.moderation === "hidden" ? `<p class="ml-item__reason"><i data-lucide="message-square-warning"></i><span><strong>Admin note:</strong> ${esc(l.reason || "Hidden by an admin.")}</span></p>` : ""}
          ${l.moderation === "pending" ? '<p class="hint">An admin will check this listing soon. It\'s not public yet.</p>' : ""}
          ${extraHTML(l)}
        </div>
        <div class="ml-item__actions">
          ${controls}
          <div class="row">
            ${canEdit ? `<button type="button" class="btn btn--outline btn--sm" data-edit="${l.id}"><i data-lucide="pencil"></i>Edit</button>` : ""}
            <button type="button" class="btn btn--outline btn--sm icon-only" data-delete="${l.id}" aria-label="Delete ${esc(l.title)}"><i data-lucide="trash-2"></i></button>
          </div>
        </div>
      </li>`;
  }

  function render() {
    const all = mine();
    const base = all.filter((l) => (!view.search || l.title.toLowerCase().includes(view.search)) && (!view.type || l.type === view.type));
    ["all", "live", "pending", "sold", "rejected"].forEach((t) => {
      $(`[data-count="${t}"]`).textContent = t === "all" ? base.length : base.filter((l) => tabOf(l) === t).length;
    });
    const key = { newest: (l) => -l.createdAt, views: (l) => -(l.views || 0), price: (l) => -Store.currentPrice(l) }[view.sort];
    const list = base.filter((l) => view.tab === "all" || tabOf(l) === view.tab).sort((a, b) => key(a) - key(b));
    $("#mlList").innerHTML = list.map(itemHTML).join("");
    $("#emptyState").hidden = list.length > 0;
    $("#emptyState strong").textContent = all.length ? "No listings here." : "You haven't listed anything yet.";
    $("#stActive").textContent = all.filter((l) => tabOf(l) === "live").length;
    $("#stPending").textContent = all.filter((l) => l.moderation === "pending").length;
    $("#stSold").textContent = all.filter((l) => l.availability === "sold").length;
    $("#stViews").textContent = all.reduce((s, l) => s + (l.views || 0), 0);
    icons();
  }

  /* ---- Controls ---- */
  $("#mlSearch").addEventListener("input", (e) => { view.search = e.target.value.trim().toLowerCase(); render(); });
  $("#typeSelect").addEventListener("change", (e) => { view.type = e.target.value; render(); });
  $("#sortSelect").addEventListener("change", (e) => { view.sort = e.target.value; render(); });
  $$(".status-tab").forEach((t) => t.addEventListener("click", () => {
    view.tab = t.dataset.tab;
    $$(".status-tab").forEach((x) => { x.classList.toggle("is-active", x === t); x.setAttribute("aria-selected", String(x === t)); });
    render();
  }));

  $("#mlList").addEventListener("change", (e) => {
    const sel = e.target.closest("[data-avail]");
    if (!sel) return;
    const l = Store.getListing(sel.dataset.avail);
    if (sel.value === "sold" && !confirm(`Mark "${l.title}" as sold? It will disappear from the marketplace.`)) { sel.value = l.availability; return; }
    Store.updateListing(l.id, { availability: sel.value });
    render();
    toast(sel.value === "sold" ? "Marked as sold. Nice deal! 🎉" : `Marked as ${sel.value}.`);
  });

  $("#mlList").addEventListener("click", (e) => {
    const del = e.target.closest("[data-delete]");
    if (del) {
      const l = Store.getListing(del.dataset.delete);
      if (!confirm(`Delete “${l.title}”? This can't be undone.`)) return;
      Store.deleteListing(l.id);
      render();
      toast(`Deleted “${l.title}”.`);
      return;
    }
    const relist = e.target.closest("[data-relist]");
    if (relist) { Store.updateListing(relist.dataset.relist, { availability: "available" }); render(); toast("Listed again."); return; }
    const acc = e.target.closest("[data-accept]");
    if (acc) { const [lid, oid] = acc.dataset.accept.split(":"); Store.respondBarterOffer(lid, oid, true); render(); toast("Offer accepted. Chat them to set the meet-up."); return; }
    const dec = e.target.closest("[data-decline]");
    if (dec) { const [lid, oid] = dec.dataset.decline.split(":"); Store.respondBarterOffer(lid, oid, false); render(); toast("Offer declined."); return; }
    const edit = e.target.closest("[data-edit]");
    if (edit) openEdit(Store.getListing(edit.dataset.edit));
  });

  /* ---- Edit dialog ---- */
  const dialog = $("#editDialog");
  let editing = null;
  const fmt = (n) => (n ? Number(n).toLocaleString("id-ID") : "");
  function openEdit(l) {
    editing = l;
    $("#eTitle").value = l.title;
    $("#ePrice").value = fmt(l.price);
    $("#ePrice").disabled = l.type === "barter";
    $("#ePriceLabel").textContent = l.type === "auction" ? "Starting bid" : l.type === "barter" ? "Price (not used for barter)" : "Price";
    $("#eCondition").value = l.condition;
    $("#eTikum").value = l.tikum;
    $("#eDesc").value = l.desc || "";
    $("#eError").hidden = true;
    $("#editTitle").textContent = l.moderation === "rejected" ? "Fix & resubmit" : "Edit listing";
    dialog.showModal();
    $("#eTitle").focus();
  }
  $("#ePrice").addEventListener("input", (e) => { const d = e.target.value.replace(/\D/g, ""); e.target.value = d ? Number(d).toLocaleString("id-ID") : ""; });

  $("#editForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const err = $("#eError");
    const title = $("#eTitle").value.trim();
    const price = Number($("#ePrice").value.replace(/\D/g, ""));
    if (title.length < 5) { err.textContent = "Title needs at least 5 characters."; err.hidden = false; return; }
    if (editing.type !== "barter" && price < 1000) { err.textContent = "Enter a price (at least Rp 1.000)."; err.hidden = false; return; }
    const desc = $("#eDesc").value.trim();
    const contentChanged = title !== editing.title || desc !== (editing.desc || "");
    const resubmit = editing.moderation === "rejected" || (contentChanged && editing.moderation === "approved");
    Store.updateListing(editing.id, { title, price: editing.type === "barter" ? 0 : price, condition: $("#eCondition").value, tikum: $("#eTikum").value, desc }, { resubmit });
    dialog.close();
    render();
    toast(resubmit ? "Saved and sent to admin for review." : "Changes saved.");
  });

  Store.onChange(render);
  setInterval(render, 30000);
  const hashTab = $(`.status-tab[data-tab="${location.hash.slice(1)}"]`);
  if (hashTab) hashTab.click(); else render();
});
