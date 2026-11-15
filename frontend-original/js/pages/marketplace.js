document.addEventListener("DOMContentLoaded", () => {
  /* ============ MARKETPLACE ============ */
  // Shows approved "Sell" listings from Store. Nothing here is sample data.
  const state = { search: "", category: "", meetup: "", condition: "", priceMin: null, priceMax: null, availability: "", sort: "newest", view: "grid" };

  const items = () => Store.publicListings("marketplace");
  function matches(l, { ignoreCategory = false } = {}) {
    if (state.search && !`${l.title} ${l.desc}`.toLowerCase().includes(state.search)) return false;
    if (!ignoreCategory && state.category && l.category !== state.category) return false;
    if (state.meetup && l.tikum !== state.meetup) return false;
    if (state.condition && l.condition !== state.condition) return false;
    if (state.priceMin !== null && l.price < state.priceMin) return false;
    if (state.priceMax !== null && l.price > state.priceMax) return false;
    if (state.availability === "available" && l.availability === "sold") return false;
    if (state.availability === "sold" && l.availability !== "sold") return false;
    if (!state.availability && l.availability === "sold") return false; // sold items only when asked
    return true;
  }
  function sortItems(list) {
    const s = [...list];
    if (state.sort === "price-asc") return s.sort((a, b) => a.price - b.price);
    if (state.sort === "price-desc") return s.sort((a, b) => b.price - a.price);
    if (state.sort === "popular") return s.sort((a, b) => (b.views || 0) - (a.views || 0));
    return s.sort((a, b) => (b.approvedAt || b.createdAt) - (a.approvedAt || a.createdAt));
  }

  function productCard(l) {
    const user = me();
    const saved = user && Store.isSaved(user.id, "listing", l.id);
    const seller = Store.getUser(l.sellerId);
    return `
      <article class="product-card${l.availability === "sold" ? " is-sold" : ""}">
        <div class="card-media">
          <span class="cat-tag cat-tag--${l.category}">${CATEGORY_LABEL[l.category]}</span>
          <button class="wish-btn${saved ? " is-active" : ""}" type="button" data-wish="${l.id}" aria-label="Save ${esc(l.title)}" aria-pressed="${!!saved}">
            <i data-lucide="heart"></i>
          </button>
          ${l.availability === "sold" ? '<span class="sold-badge">SOLD</span>' : l.availability === "reserved" ? '<span class="sold-badge sold-badge--reserved">RESERVED</span>' : ""}
          ${photoOrPlaceholder(listingPhoto(l), l.title)}
        </div>
        <div class="card-body">
          <h3 class="card-title"><a href="item.html?id=${l.id}">${esc(l.title)}</a></h3>
          <div class="price-row">
            <strong class="price">${rupiah(l.price)}</strong>
            <span class="cond cond--${l.condition}">${CONDITION_LABEL[l.condition]}</span>
          </div>
          <div class="seller">
            ${avatarHTML(seller, 22).replace('class="avatar-lg"', 'class="avatar-lg seller__avatar"')}
            <span class="seller__name">${esc(seller?.name || "Student")}</span>
            ${l.nego ? '<span class="seller__rating">Nego</span>' : ""}
          </div>
          <span class="location"><i data-lucide="map-pin"></i> ${esc(l.tikum)}</span>
        </div>
      </article>`;
  }

  function renderProducts() {
    const all = items();
    const list = sortItems(all.filter((l) => matches(l)));
    const grid = $("#productGrid");
    grid.innerHTML = list.map(productCard).join("");
    grid.classList.toggle("is-list", state.view === "list");
    $("#resultCount").textContent = list.length;
    $("#emptyState").hidden = list.length > 0;
    const empty = $("#emptyState");
    if (!all.length) {
      empty.innerHTML = `<p><strong>No items yet.</strong></p><p>Be the first to sell something on GaMarketplace!</p><a href="list-item.html" class="btn btn--primary btn--keep"><i data-lucide="plus"></i><span>List an Item</span></a>`;
    } else {
      empty.innerHTML = `<p><strong>No items match these filters.</strong></p><p>Try another meet-up point or category, or reset the filters.</p><button type="button" class="btn btn--outline" id="emptyClearBtn">Reset filters</button>`;
      $("#emptyClearBtn").addEventListener("click", clearFilters);
    }
    $('[data-cat-count="all"]').textContent = all.filter((l) => matches(l, { ignoreCategory: true })).length;
    icons();
  }

  // Right panel: items at your usual tikum (or the newest ones)
  function renderNearby() {
    const user = me();
    const tikum = user && user.tikum;
    let list = items().filter((l) => l.availability !== "sold" && (!user || l.sellerId !== user.id));
    const near = tikum ? list.filter((l) => l.tikum === tikum) : [];
    $("#nearbyTitle").textContent = near.length ? `Near your tikum (${tikum})` : "Newest Listings";
    list = sortItems(near.length ? near : list).slice(0, 3);
    $("#nearbyList").innerHTML = list.length ? list.map((l) => `
      <li class="nearby-item">
        ${listingPhoto(l) ? `<img src="${listingPhoto(l)}" alt="" loading="lazy" />` : '<span class="nearby-ph" aria-hidden="true"><i data-lucide="image"></i></span>'}
        <div class="nearby-item__body">
          <div class="nearby-item__top">
            <a href="item.html?id=${l.id}">${esc(l.title)}</a>
            <time>${timeAgo(l.approvedAt || l.createdAt)}</time>
          </div>
          <strong class="price">${rupiah(l.price)}</strong>
          <span class="location"><i data-lucide="map-pin"></i> ${esc(l.tikum)}</span>
        </div>
      </li>`).join("") : '<li class="hint">No listings yet.</li>';
    $("#nearbySeeAll").hidden = !list.length;
    if (near.length) $("#nearbySeeAll").onclick = () => { state.meetup = tikum; syncControls(); renderProducts(); };
  }

  /* ---- Controls ---- */
  let pricePop = null;
  function syncControls() {
    $$(".cat-tab").forEach((tab) => {
      const on = tab.dataset.cat === (state.category || "all");
      tab.classList.toggle("is-active", on);
      tab.setAttribute("aria-pressed", String(on));
    });
    $("#meetupSelect").value = state.meetup;
    $$("[data-filter]").forEach((sel) => { sel.value = state[sel.dataset.filter]; sel.closest(".pill-select").classList.toggle("is-set", !!sel.value); });
    const n = ["condition", "availability"].filter((k) => state[k]).length + (state.priceMin !== null || state.priceMax !== null ? 1 : 0);
    $("#filterCount").textContent = n;
    $("#filterCount").hidden = n === 0;
    if (pricePop) pricePop.sync();
  }
  function clearFilters() {
    Object.assign(state, { search: "", category: "", meetup: "", condition: "", priceMin: null, priceMax: null, availability: "" });
    $("#itemSearch").value = "";
    $("#searchInput").value = "";
    syncControls();
    renderProducts();
  }

  const onSearch = (e) => {
    state.search = e.target.value.trim().toLowerCase();
    $("#itemSearch").value = e.target.value;
    $("#searchInput").value = e.target.value;
    renderProducts();
  };
  $("#searchForm").addEventListener("submit", (e) => e.preventDefault());
  $("#searchInput").addEventListener("input", onSearch);
  $("#itemSearch").addEventListener("input", onSearch);
  $("#meetupSelect").addEventListener("change", (e) => { state.meetup = e.target.value; renderProducts(); });
  $("#sortSelect").addEventListener("change", (e) => { state.sort = e.target.value; renderProducts(); });
  $$("[data-filter]").forEach((sel) => sel.addEventListener("change", () => { state[sel.dataset.filter] = sel.value; syncControls(); renderProducts(); }));
  pricePop = setupPricePopover({
    emptyText: "Price Range",
    getRange: () => ({ min: state.priceMin, max: state.priceMax }),
    onApply: (min, max) => { state.priceMin = min; state.priceMax = max; syncControls(); renderProducts(); },
  });
  $$(".cat-tab").forEach((tab) => tab.addEventListener("click", () => { state.category = tab.dataset.cat === "all" ? "" : tab.dataset.cat; syncControls(); renderProducts(); }));
  $("#filterToggle").addEventListener("click", (e) => {
    const open = e.currentTarget.getAttribute("aria-expanded") !== "true";
    e.currentTarget.setAttribute("aria-expanded", String(open));
    $("#filterSelects").hidden = !open;
  });
  if (window.matchMedia("(max-width: 760px)").matches) { $("#filterToggle").setAttribute("aria-expanded", "false"); $("#filterSelects").hidden = true; }
  $$(".view-btn").forEach((btn) => btn.addEventListener("click", () => {
    state.view = btn.dataset.view;
    $$(".view-btn").forEach((b) => { b.classList.toggle("is-active", b === btn); b.setAttribute("aria-pressed", String(b === btn)); });
    renderProducts();
  }));
  $("#resetBtn").addEventListener("click", clearFilters);

  // Wishlist hearts
  $("#productGrid").addEventListener("click", (e) => {
    const btn = e.target.closest("[data-wish]");
    if (!btn || !requireLogin("Log in to save items to your wishlist.")) return;
    const l = Store.getListing(btn.dataset.wish);
    if (l.sellerId === me().id) return toast("That's your own item.");
    const on = Store.toggleSaved(me().id, "listing", l.id);
    btn.classList.toggle("is-active", on);
    btn.setAttribute("aria-pressed", String(on));
    toast(on ? "Saved to your wishlist" : "Removed from your wishlist");
  });

  // Use my location (real browser location → distance to campus)
  const UGM = { lat: -7.7713, lng: 110.3775 }; // approx. center of UGM campus
  const distanceKm = (a, b) => {
    const R = 6371, toRad = (d) => (d * Math.PI) / 180;
    const dLat = toRad(b.lat - a.lat), dLng = toRad(b.lng - a.lng);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
  };
  $("#useLocationBtn").addEventListener("click", (e) => {
    const btn = e.currentTarget;
    if (!("geolocation" in navigator)) return toast("Your browser can't share your location.");
    btn.classList.add("is-loading");
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        btn.classList.remove("is-loading");
        btn.classList.add("is-on");
        const km = distanceKm(UGM, { lat: pos.coords.latitude, lng: pos.coords.longitude });
        $("#distanceBadge strong").textContent = `± ${km < 10 ? km.toFixed(1) : Math.round(km)} km`;
        $("#distanceBadge small").textContent = "from campus";
        toast(`Location on: you're about ${km.toFixed(1)} km from UGM.`);
      },
      () => { btn.classList.remove("is-loading"); toast("Location is blocked. Allow location access in your browser settings and try again."); },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  });

  // marketplace.html?meetup=FT (from the Meet-Up Map) or ?q=… (from the top search on other pages)
  const params = new URLSearchParams(location.search);
  if (params.get("meetup") && FACULTIES.includes(params.get("meetup"))) state.meetup = params.get("meetup");
  if (params.get("q")) { state.search = params.get("q").toLowerCase(); $("#itemSearch").value = params.get("q"); $("#searchInput").value = params.get("q"); }

  Store.onChange(() => { renderProducts(); renderNearby(); });
  renderNearby();
  syncControls();
  renderProducts();
});
