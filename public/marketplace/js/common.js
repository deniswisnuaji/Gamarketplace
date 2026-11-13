/* =========================================================
   GaMarketplace — shared helpers (load after store.js)
   ========================================================= */
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const rupiah = Store.rupiah;
const esc = (str) => String(str ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const initialsOf = (name) => String(name || "?").trim().split(/\s+/).map((w) => w[0]).join("").slice(0, 2).toUpperCase();
const icons = () => { if (window.lucide) lucide.createIcons(); };

const FACULTIES = ["Biologi", "FEB", "Farmasi", "Filsafat", "Geografi", "FH", "FIB", "FISIPOL", "FK-KMK", "FKG", "FKH", "FKT", "FMIPA", "FAPERTA", "FAPET", "Psikologi", "FT", "FTP", "SV", "SPs"];
const CATEGORY_LABEL = { education: "Education", electronics: "Electronics", furniture: "Furniture", fashion: "Fashion", hobbies: "Hobbies", "self-care": "Self-Care", others: "Others" };
const CONDITION_LABEL = { "like-new": "Like New", good: "Good", fair: "Fair" };
const TYPE_LABEL = { marketplace: "Marketplace", auction: "Auction", barter: "Barter" };

function timeAgo(t) {
  const m = Math.max(0, Math.round((Date.now() - t) / 60000));
  if (m < 1) return "just now";
  if (m < 60) return `${m} min ago`;
  if (m < 1440) return `${Math.round(m / 60)} h ago`;
  if (m < 43200) return `${Math.round(m / 1440)} d ago`;
  return new Date(t).toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
}
function formatLeft(ms) {
  if (ms <= 0) return "Ended";
  const s = Math.floor(ms / 1000), d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
  if (d > 0) return `${d}d ${h}h`;
  if (h > 0) return `${h}h ${m}m`;
  if (m >= 5) return `${m}m`;
  if (m > 0) return `${m}m ${String(sec).padStart(2, "0")}s`;
  return `${sec}s`;
}
// Small round avatar: photo if the user has one, otherwise initials
function avatarHTML(user, size = 32) {
  const style = `width:${size}px;height:${size}px;font-size:${Math.round(size * 0.38)}px`;
  if (!user) return `<span class="avatar-lg" style="${style}" aria-hidden="true">?</span>`;
  return user.photo
    ? `<span class="avatar-lg" style="${style}" aria-hidden="true"><img src="${user.photo}" alt="" /></span>`
    : `<span class="avatar-lg" style="${style}" aria-hidden="true">${esc(initialsOf(user.name))}</span>`;
}
const listingPhoto = (l) => (l.photos && l.photos[0]) || "";
const photoOrPlaceholder = (src, alt = "") => (src ? `<img src="${src}" alt="${esc(alt)}" loading="lazy" />` : `<span class="no-photo" aria-hidden="true"><i data-lucide="image"></i></span>`);

function toast(msg, action) {
  const t = $("#toast");
  if (!t) return;
  t.innerHTML = esc(msg) + (action ? ` <button type="button" class="toast__action">${esc(action.label)}</button>` : "");
  t.hidden = false;
  if (action) t.querySelector(".toast__action").onclick = () => { action.run(); t.hidden = true; };
  clearTimeout(toast.timer);
  toast.timer = setTimeout(() => (t.hidden = true), action ? 6000 : 3500);
}

// Close any <dialog> with [data-close] buttons or by clicking the dark backdrop
$$("[data-close]").forEach((b) => b.addEventListener("click", () => b.closest("dialog").close()));
$$("dialog").forEach((d) => d.addEventListener("click", (e) => { if (e.target === d) d.close(); }));

/* ---------- Who is logged in ---------- */
const me = () => Store.currentUser();
const loginURL = () => `login.html?next=${encodeURIComponent(location.pathname.split("/").pop() + location.search + location.hash)}`;
// Use on actions that need an account (bid, save, offer…). Returns true when logged in.
function requireLogin(message) {
  if (me()) return true;
  toast(message || "Please log in first.", { label: "Log in", run: () => (location.href = loginURL()) });
  return false;
}
// Use at the top of pages that only make sense when logged in (My Listings, Settings…)
function requirePage() {
  if (me()) return true;
  location.replace(loginURL());
  return false;
}

/* ---------- Log in first ----------
   Every student page needs an account. Without one, you go to the login page
   (and come back to the page you wanted after logging in). */
const OPEN_PAGES = ["login.html", "logout.html", "admin-login.html", "admin.html"]; // admin.html checks admins itself
(() => {
  const page = location.pathname.split("/").pop() || "main.html";
  if (!OPEN_PAGES.includes(page) && !me()) location.replace(loginURL());
})();

/* ---------- Top bar: avatar, menu, unread badges ---------- */
function renderSessionUI() {
  const user = me();
  const profile = $(".topbar .profile");
  if (profile) {
    if (!user) {
      // keep the menu elements (layout.js uses them) but hide them, and show a Log in button
      $("#profileToggle") && ($("#profileToggle").hidden = true);
      if (!profile.querySelector(".topbar-login")) {
        profile.insertAdjacentHTML("afterbegin", `<a href="${loginURL()}" class="btn btn--outline topbar-login"><i data-lucide="log-in"></i><span>Log in</span></a>`);
      }
    } else {
      const toggle = $("#profileToggle");
      const avatar = toggle && toggle.querySelector("img.avatar, span.avatar:not(.admin-avatar)");
      if (avatar) avatar.outerHTML = avatarHTML(user, 44).replace('class="avatar-lg"', 'class="avatar-lg avatar"');
      const menu = $("#profileMenu");
      if (menu && !menu.querySelector(".profile__who")) {
        menu.insertAdjacentHTML("afterbegin", `<li role="none" class="profile__who"><strong>${esc(user.name)}</strong><span>${esc(user.email)}</span></li>`);
      }
    }
  }
  // Unread counts on the bell and the messages icon
  const setBadge = (el, n) => {
    if (!el) return;
    el.querySelector(".badge-dot")?.remove();
    let b = el.querySelector(".bell-count");
    if (!n) { b?.remove(); return; }
    if (!b) { b = document.createElement("span"); b.className = "bell-count"; el.appendChild(b); }
    b.textContent = n > 9 ? "9+" : n;
  };
  const bell = $('.topbar a[href="notifications.html"]');
  const chat = $('.topbar a[href="messages.html"]');
  setBadge(bell, user ? Store.unreadCount(user.id) : 0);
  setBadge(chat, user ? Store.unreadMessages(user.id) : 0);
  if (bell) bell.setAttribute("aria-label", user && Store.unreadCount(user.id) ? `Notifications (${Store.unreadCount(user.id)} unread)` : "Notifications");
  icons();
}
renderSessionUI();
Store.onChange(renderSessionUI);
setInterval(() => { Store.finalizeAuctions(); renderSessionUI(); }, 30000);
