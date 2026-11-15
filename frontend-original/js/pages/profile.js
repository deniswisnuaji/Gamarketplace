document.addEventListener("DOMContentLoaded", () => {
  /* ============ PROFILE ============ */
  // profile.html            → your own profile (needs login)
  // profile.html?u=<userId> → another student's public profile
  const viewer = me();
  const uid = new URLSearchParams(location.search).get("u");
  if (!uid && !requirePage()) return;
  const user = Store.getUser(uid || viewer.id);
  const isSelf = viewer && user && viewer.id === user.id;

  if (!user || user.role === "admin" || (user.status !== "active" && !isSelf)) {
    $("#main").innerHTML = `<div class="empty-box"><i data-lucide="user-x"></i><strong>This profile isn't available.</strong><a href="marketplace.html" class="btn btn--primary btn--keep"><span>Back to Marketplace</span></a></div>`;
    $("#strengthPanel").hidden = true;
    icons();
    return;
  }

  function render() {
    const u = Store.getUser(user.id);
    const listings = Store.listings().filter((l) => l.sellerId === u.id);
    const live = listings.filter((l) => Store.isPublic(l) && l.availability !== "sold");
    $("#pAvatar").innerHTML = u.photo ? `<img src="${u.photo}" alt="" />` : esc(initialsOf(u.name));
    $("#pName").textContent = u.name;
    $("#pUsername").textContent = u.username || u.email.split("@")[0];
    $("#pBio").textContent = u.bio || (isSelf ? "No bio yet. Add one in Settings." : "No bio yet.");
    $("#pFaculty").textContent = u.faculty;
    $("#pFacultyRow").hidden = u.showFaculty === false && !isSelf;
    $("#pTikum").textContent = u.tikum || "–";
    const joined = new Date(u.createdAt).toLocaleDateString("en-GB", { month: "short", year: "numeric" });
    $("#pJoined").textContent = joined;
    $("#statListings").textContent = live.length;
    $("#statSold").textContent = listings.filter((l) => l.availability === "sold").length;
    $("#statRequests").textContent = Store.requests().filter((r) => r.userId === u.id).length;
    $("#statJoined").textContent = joined;
    $("#aFaculty").textContent = u.showFaculty === false ? (isSelf ? `${u.faculty} (hidden from others)` : "Hidden") : u.faculty;
    $("#aTikum").textContent = u.tikum || "–";
    $("#aPhone").textContent = u.showPhone && u.phone ? u.phone : isSelf ? (u.phone ? `${u.phone} (hidden from others)` : "Not added") : "Hidden (chat in app)";
    $("#aJoined").textContent = joined;
    document.title = `${u.name} — GaMarketplace`;

    // Buttons: your own profile vs someone else's
    $("#editProfileBtn").hidden = !isSelf;
    $("#manageBtn").hidden = !isSelf;
    $("#strengthPanel").hidden = !isSelf;
    const chat = $("#chatUserBtn");
    chat.hidden = isSelf;
    chat.href = viewer ? `messages.html?to=${u.id}` : `login.html?next=${encodeURIComponent("profile.html?u=" + u.id)}`;

    const shown = isSelf ? listings.filter((l) => l.availability !== "sold") : live;
    $("#listingGrid").innerHTML = shown.length ? shown.map((l) => `
      <article class="mini-card">
        <div class="card-media">
          <span class="type-badge">${TYPE_LABEL[l.type]}${isSelf && l.moderation !== "approved" ? ` · ${l.moderation === "pending" ? "In review" : l.moderation}` : ""}</span>
          ${photoOrPlaceholder(listingPhoto(l), l.title)}
        </div>
        <div class="card-body">
          <h3 class="card-title"><a href="item.html?id=${l.id}">${esc(l.title)}</a></h3>
          <strong class="price">${l.type === "barter" ? "Open to swap" : rupiah(Store.currentPrice(l))}</strong>
          <span class="location"><i data-lucide="map-pin"></i> ${esc(l.tikum)}</span>
        </div>
      </article>`).join("")
      : `<div class="empty-box"><i data-lucide="package"></i><strong>${isSelf ? "You have no listings yet." : "No live listings."}</strong>${isSelf ? '<a href="list-item.html" class="btn btn--primary btn--keep"><i data-lucide="plus"></i><span>List an Item</span></a>' : ""}</div>`;

    if (isSelf) {
      const checks = [
        ["Profile photo", !!u.photo],
        ["Bio (20+ characters)", (u.bio || "").length >= 20],
        ["Default meet-up point", !!u.tikum],
        ["WhatsApp number (can stay private)", !!u.phone],
        ["Signed up with UGM email", true],
      ];
      const pct = Math.round((checks.filter((c) => c[1]).length / checks.length) * 100);
      $("#strengthText").textContent = `${pct}%`;
      $("#strengthBar").style.width = `${pct}%`;
      $("#strengthList").innerHTML = checks.map(([label, ok]) => `<li class="${ok ? "is-done" : "is-todo"}"><i data-lucide="${ok ? "circle-check" : "circle"}"></i>${label}</li>`).join("");
    }
    icons();
  }

  $$(".status-tab").forEach((t) => t.addEventListener("click", () => {
    $$(".status-tab").forEach((x) => {
      const on = x === t;
      x.classList.toggle("is-active", on);
      x.setAttribute("aria-selected", String(on));
      $(`#${x.getAttribute("aria-controls")}`).hidden = !on;
    });
  }));
  $("#shareProfile").addEventListener("click", async () => {
    const url = `${location.origin}${location.pathname}?u=${encodeURIComponent(user.id)}`;
    try {
      if (navigator.share) await navigator.share({ title: `${user.name} on GaMarketplace`, url });
      else { await navigator.clipboard.writeText(url); toast("Profile link copied."); }
    } catch {}
  });
  render();
  Store.onChange(render);
});
