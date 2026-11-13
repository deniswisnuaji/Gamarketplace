/* =========================================================
   GaMarketplace — local data store
   ---------------------------------------------------------
   All data (users, listings, bids, offers, chats, reports…)
   is saved in this browser's localStorage. Nothing is fake:
   everything you see was created by a user or an admin.

   Every page reads and writes data ONLY through `Store`.
   When the Laravel backend is ready, replace the functions
   in this file with requests to the backend routes.

   Note: localStorage is per browser. To demo several users,
   register more accounts and switch between them.
   ========================================================= */
const Store = (() => {
  const DB_KEY = "gm-db";
  const SESSION_KEY = "gm-session";
  const VERSION = 1;

  const empty = () => ({
    version: VERSION, users: [], listings: [], requests: [], reports: [],
    conversations: [], notifications: [], adminLog: [],
  });

  let db = load();

  function load() {
    try {
      const raw = JSON.parse(localStorage.getItem(DB_KEY) || "null");
      if (raw && raw.version === VERSION) return { ...empty(), ...raw };
    } catch { /* corrupted → start fresh */ }
    return empty();
  }
  function save() {
    try {
      localStorage.setItem(DB_KEY, JSON.stringify(db));
    } catch (e) {
      throw new Error("Browser storage is full. Use fewer or smaller photos, or delete old listings.");
    }
  }
  // Keep tabs in sync (e.g. a message sent in another tab)
  const listeners = new Set();
  window.addEventListener("storage", (e) => { if (e.key === DB_KEY) { db = load(); listeners.forEach((fn) => fn()); } });
  const onChange = (fn) => listeners.add(fn);

  const uid = () => Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
  const now = () => Date.now();

  /* ---------- Password hashing (demo only — real hashing happens on the server) ---------- */
  async function hash(email, password) {
    const text = `gm:${email.toLowerCase()}:${password}`;
    if (window.crypto && crypto.subtle) {
      const buf = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(text));
      return [...new Uint8Array(buf)].map((b) => b.toString(16).padStart(2, "0")).join("");
    }
    let h = 5381; for (const c of text) h = ((h << 5) + h + c.charCodeAt(0)) | 0; return "x" + (h >>> 0).toString(16);
  }

  /* ---------- First run: create the admin account (config, not demo data) ---------- */
  // CHANGE THIS before showing the site to anyone. The real admin account belongs in the backend.
  const DEFAULT_ADMIN = { name: "Admin GaMarketplace", email: "admin@ugm.ac.id", password: "Admin2026" };
  // The "Continue with UGM account" button signs in as this student for now.
  // Real UGM SSO must be connected by the backend later.
  const UGM_ACCOUNT = { name: "UGM Student", email: "user@mail.ugm.ac.id", password: "User2026" };
  async function ensureAccounts() {
    await null; // wait until the rest of this file has loaded (findByEmail etc.)
    let changed = false;
    if (!db.users.some((u) => u.role === "admin")) {
      db.users.push({
        id: uid(), role: "admin", status: "active", name: DEFAULT_ADMIN.name, email: DEFAULT_ADMIN.email,
        passwordHash: await hash(DEFAULT_ADMIN.email, DEFAULT_ADMIN.password), faculty: "", createdAt: now(),
      });
      changed = true;
    }
    if (!findByEmail(UGM_ACCOUNT.email)) {
      db.users.push({
        id: uid(), role: "student", status: "active", name: UGM_ACCOUNT.name, email: UGM_ACCOUNT.email,
        passwordHash: await hash(UGM_ACCOUNT.email, UGM_ACCOUNT.password), faculty: "", tikum: "", bio: "", phone: "", photo: "",
        showPhone: false, showFaculty: true, prefs: {}, wishlist: [], createdAt: now(), viaUGM: true,
      });
      changed = true;
    }
    if (changed) save();
  }
  const ready = ensureAccounts();

  // "Continue with UGM account"
  async function loginWithUGM() {
    await ready;
    const u = findByEmail(UGM_ACCOUNT.email);
    if (u.status !== "active") return { error: "suspended" };
    setSession(u.id, true);
    return { user: u };
  }

  /* ---------- Users & session ---------- */
  const users = () => db.users;
  const getUser = (id) => db.users.find((u) => u.id === id) || null;
  const findByEmail = (email) => db.users.find((u) => u.email.toLowerCase() === String(email).toLowerCase()) || null;

  // A tab-only login (sessionStorage, "keep me logged in" unchecked) wins over the saved one.
  // Tip for demos: log in as student A normally, then in another tab log in as student B
  // with "keep me logged in" unchecked — both tabs work at the same time and update live.
  function sessionId() {
    try { return sessionStorage.getItem(SESSION_KEY) || localStorage.getItem(SESSION_KEY); } catch { return null; }
  }
  function currentUser() {
    const u = getUser(sessionId());
    return u && u.status === "active" ? u : null;
  }
  function setSession(id, remember) {
    try {
      sessionStorage.removeItem(SESSION_KEY);
      if (remember) localStorage.setItem(SESSION_KEY, id); else sessionStorage.setItem(SESSION_KEY, id);
    } catch {}
  }
  function logout() {
    try {
      if (sessionStorage.getItem(SESSION_KEY)) sessionStorage.removeItem(SESSION_KEY); // only this tab
      else localStorage.removeItem(SESSION_KEY);
    } catch {}
  }

  async function register({ name, email, faculty, password }) {
    await ready;
    if (findByEmail(email)) return { error: "An account with this email already exists. Try logging in." };
    const user = {
      id: uid(), role: "student", status: "active", name: name.trim(), email: email.trim().toLowerCase(),
      passwordHash: await hash(email, password), faculty, tikum: faculty, bio: "", phone: "", photo: "",
      showPhone: false, showFaculty: true, prefs: {}, wishlist: [], createdAt: now(),
    };
    db.users.push(user); save();
    return { user };
  }
  async function login(email, password, { admin = false, remember = true } = {}) {
    await ready;
    const u = findByEmail(email);
    if (!u || u.passwordHash !== (await hash(email, password))) return { error: "wrong" };
    if (admin && u.role !== "admin") return { error: "wrong" };
    if (!admin && u.role === "admin") return { error: "admin" };
    if (u.status !== "active") return { error: "suspended" };
    setSession(u.id, remember);
    return { user: u };
  }
  async function changePassword(userId, oldPw, newPw) {
    const u = getUser(userId);
    if (u.passwordHash !== (await hash(u.email, oldPw))) return { error: "Current password is incorrect." };
    u.passwordHash = await hash(u.email, newPw); save();
    return { ok: true };
  }
  function updateUser(id, patch) { Object.assign(getUser(id), patch); save(); return getUser(id); }
  function deleteUser(id) {
    db.listings = db.listings.filter((l) => l.sellerId !== id);
    db.requests = db.requests.filter((r) => r.userId !== id);
    db.conversations = db.conversations.filter((c) => !c.members.includes(id));
    db.notifications = db.notifications.filter((n) => n.userId !== id);
    db.users = db.users.filter((u) => u.id !== id);
    save();
  }
  function setUserStatus(id, status, reason) {
    const u = getUser(id);
    u.status = status;
    if (status === "suspended") {
      db.listings.filter((l) => l.sellerId === id && l.moderation === "approved").forEach((l) => { l.moderation = "hidden"; l.hiddenBecauseSuspended = true; });
    } else {
      db.listings.filter((l) => l.sellerId === id && l.hiddenBecauseSuspended).forEach((l) => { l.moderation = "approved"; delete l.hiddenBecauseSuspended; });
    }
    log(status === "suspended" ? "suspend" : "info", `${status === "suspended" ? "Suspended" : "Restored"} ${u.name}${reason ? " — " + reason : ""}`);
    save();
  }
  const admins = () => db.users.filter((u) => u.role === "admin");

  /* ---------- Wishlist (stored on the user) ---------- */
  function isSaved(userId, kind, id) { const u = getUser(userId); return !!u && (u.wishlist || []).some((w) => w.kind === kind && w.id === id); }
  function toggleSaved(userId, kind, id) {
    const u = getUser(userId);
    u.wishlist = u.wishlist || [];
    const i = u.wishlist.findIndex((w) => w.kind === kind && w.id === id);
    const l = kind === "listing" ? getListing(id) : null;
    if (i >= 0) u.wishlist.splice(i, 1); else u.wishlist.unshift({ kind, id, at: now(), price: l ? currentPrice(l) : null });
    save();
    return i < 0;
  }

  /* ---------- Notifications ---------- */
  const PREF_FOR = { outbid: "notifOutbid", offer: "notifOffers", message: "notifMessages", scam: "notifScam", drop: "notifPriceDrop" };
  function notify(userId, type, text, link) {
    if (!userId) return;
    const prefs = (getUser(userId) || {}).prefs || {};
    if (PREF_FOR[type] && prefs[PREF_FOR[type]] === false) return; // user turned this off in Settings
    db.notifications.unshift({ id: uid(), userId, type, text, link: link || "", read: false, at: now() });
    db.notifications = db.notifications.slice(0, 500);
  }
  const notifyAdmins = (type, text, link) => admins().forEach((a) => notify(a.id, type, text, link));
  const notificationsFor = (userId) => db.notifications.filter((n) => n.userId === userId);
  const unreadCount = (userId) => db.notifications.filter((n) => n.userId === userId && !n.read).length;
  function markNotification(id, read = true) { const n = db.notifications.find((x) => x.id === id); if (n) { n.read = read; save(); } }
  function markAllNotifications(userId) { db.notifications.forEach((n) => { if (n.userId === userId) n.read = true; }); save(); }
  function deleteNotification(id) { db.notifications = db.notifications.filter((n) => n.id !== id); save(); }

  /* ---------- Admin activity log ---------- */
  function log(kind, text) {
    const me = currentUser() || admins()[0];
    db.adminLog.unshift({ id: uid(), adminId: me && me.id, kind, text, at: now() });
    db.adminLog = db.adminLog.slice(0, 300);
  }

  /* ---------- Listings (marketplace / auction / barter) ---------- */
  // moderation: pending | approved | rejected | hidden      availability: available | reserved | sold
  const listings = () => db.listings;
  const getListing = (id) => db.listings.find((l) => l.id === id) || null;
  const isPublic = (l) => l.type !== "barter" && l.moderation === "approved" && (getUser(l.sellerId) || {}).status === "active"; // barter was removed
  const publicListings = (type) => db.listings.filter((l) => isPublic(l) && (!type || l.type === type));

  function createListing(data) {
    const me = currentUser();
    const l = {
      id: uid(), sellerId: me.id, moderation: "pending", availability: "available",
      views: 0, createdAt: now(), updatedAt: now(), ...data,
    };
    if (l.type === "auction") l.auction = { ...l.auction, bids: [], endsAt: null, finalized: false };
    if (l.type === "barter") l.barter = { ...l.barter, offers: [] };
    db.listings.unshift(l);
    notifyAdmins("review", `New listing to review: “${l.title}” by ${me.name}`, "admin.html#review");
    save();
    return l;
  }
  function updateListing(id, patch, { resubmit = false } = {}) {
    const l = getListing(id);
    const oldPrice = l.price;
    Object.assign(l, patch, { updatedAt: now() });
    // Tell people who saved it when a "Sell" item gets cheaper
    if (l.type === "marketplace" && patch.price && patch.price < oldPrice && l.moderation === "approved") {
      db.users.filter((u) => (u.wishlist || []).some((w) => w.id === l.id))
        .forEach((u) => notify(u.id, "drop", `Price drop! “${l.title}” is now ${rupiah(patch.price)} (was ${rupiah(oldPrice)}).`, `item.html?id=${l.id}`));
    }
    if (resubmit && l.moderation !== "pending") {
      l.moderation = "pending"; l.reason = "";
      notifyAdmins("review", `Listing updated and needs review: “${l.title}”`, "admin.html#review");
    }
    save();
    return l;
  }
  function deleteListing(id) {
    db.listings = db.listings.filter((l) => l.id !== id);
    db.users.forEach((u) => { if (u.wishlist) u.wishlist = u.wishlist.filter((w) => w.id !== id); });
    save();
  }
  function moderate(id, decision, reason = "") {
    const l = getListing(id);
    l.moderation = decision; // approved | rejected | hidden | pending
    l.reason = decision === "rejected" ? reason : "";
    if (decision === "approved") {
      l.approvedAt = l.approvedAt || now();
      if (l.type === "auction" && !l.auction.endsAt) l.auction.endsAt = now() + (l.auction.durationDays || 3) * 86400e3;
      notify(l.sellerId, "approved", `Your listing “${l.title}” was approved and is now live.`, "my-listings.html");
      log("approve", l.title);
    } else if (decision === "rejected") {
      notify(l.sellerId, "rejected", `Your listing “${l.title}” needs changes: ${reason}`, "my-listings.html#rejected");
      log("reject", `${l.title} — ${reason}`);
    } else if (decision === "hidden") {
      notify(l.sellerId, "rejected", `Your listing “${l.title}” was hidden by an admin${reason ? ": " + reason : "."}`, "my-listings.html");
      log("delete", `Hidden: ${l.title}${reason ? " — " + reason : ""}`);
    }
    save();
  }
  function addView(id) { const l = getListing(id); if (l) { l.views = (l.views || 0) + 1; save(); } }

  /* ---------- Auctions ---------- */
  const highestBid = (l) => (l.type === "auction" && l.auction && l.auction.bids.length ? l.auction.bids[l.auction.bids.length - 1] : null);
  const currentPrice = (l) => (highestBid(l) ? highestBid(l).amount : l.price);
  const increment = (price) => (price < 100000 ? 5000 : price < 1000000 ? 25000 : 50000);
  const minNextBid = (l) => (highestBid(l) ? currentPrice(l) + increment(currentPrice(l)) : l.price);
  const auctionEnded = (l) => !!l.auction.endsAt && (l.auction.endsAt <= now() || l.auction.finalized);

  function placeBid(id, amount) {
    const me = currentUser(), l = getListing(id);
    if (!isPublic(l) || auctionEnded(l)) return { error: "This auction has ended." };
    if (l.sellerId === me.id) return { error: "You can't bid on your own auction." };
    if (amount < minNextBid(l)) return { error: `Your bid must be at least ${rupiah(minNextBid(l))}.` };
    const prev = highestBid(l);
    if (prev && prev.userId === me.id) return { error: "You're already the highest bidder." };
    l.auction.bids.push({ userId: me.id, amount, at: now() });
    notify(l.sellerId, "offer", `New bid ${rupiah(amount)} on your auction “${l.title}”.`, "bidding.html");
    if (prev) notify(prev.userId, "outbid", `You've been outbid on “${l.title}”. New bid: ${rupiah(amount)}.`, "bidding.html");
    save();
    return { ok: true };
  }
  function buyNow(id) {
    const me = currentUser(), l = getListing(id);
    if (!l.auction.buyNow || auctionEnded(l) || l.sellerId === me.id) return { error: "Buy Now isn't available." };
    l.auction.bids.push({ userId: me.id, amount: l.auction.buyNow, at: now(), buyNow: true });
    l.auction.endsAt = now();
    save();
    finalizeAuctions();
    return { ok: true };
  }
  // Close auctions whose time is up: tell the winner and the seller.
  function finalizeAuctions() {
    let changed = false;
    db.listings.forEach((l) => {
      if (l.type !== "auction" || l.auction.finalized || !l.auction.endsAt || l.auction.endsAt > now()) return;
      l.auction.finalized = true; changed = true;
      const win = highestBid(l);
      if (win) {
        l.availability = "sold";
        notify(win.userId, "won", `🎉 You won “${l.title}” for ${rupiah(win.amount)}. Chat the seller to arrange the meet-up.`, `messages.html?to=${l.sellerId}&listing=${l.id}`);
        notify(l.sellerId, "won", `Your auction “${l.title}” ended. Winner: ${getUser(win.userId)?.name || "a student"} (${rupiah(win.amount)}).`, `messages.html?to=${win.userId}&listing=${l.id}`);
      } else {
        notify(l.sellerId, "info", `Your auction “${l.title}” ended with no bids. You can relist it.`, "my-listings.html");
      }
    });
    if (changed) save();
  }

  /* ---------- Barter offers ---------- */
  function makeBarterOffer(id, offer) {
    const me = currentUser(), l = getListing(id);
    if (l.sellerId === me.id) return { error: "This is your own listing." };
    if (l.barter.offers.some((o) => o.userId === me.id && o.status === "sent")) return { error: "You already sent an offer." };
    const o = { id: uid(), userId: me.id, status: "sent", at: now(), ...offer };
    l.barter.offers.push(o);
    notify(l.sellerId, "offer", `${me.name} offered “${o.itemTitle}”${o.cash ? " + " + rupiah(o.cash) : ""} for your “${l.title}”.`, "my-listings.html");
    save();
    return { offer: o };
  }
  function cancelBarterOffer(id, offerId) { const l = getListing(id); l.barter.offers = l.barter.offers.filter((o) => o.id !== offerId); save(); }
  function respondBarterOffer(id, offerId, accept) {
    const l = getListing(id), o = l.barter.offers.find((x) => x.id === offerId);
    o.status = accept ? "accepted" : "declined";
    if (accept) {
      l.availability = "reserved";
      l.barter.offers.forEach((x) => { if (x !== o && x.status === "sent") { x.status = "declined"; notify(x.userId, "info", `Your offer for “${l.title}” was not chosen.`, "my-listings.html"); } });
      notify(o.userId, "approved", `Your barter offer for “${l.title}” was accepted! Chat to set the meet-up.`, `messages.html?to=${l.sellerId}&listing=${l.id}`);
    } else {
      notify(o.userId, "info", `Your barter offer for “${l.title}” was declined.`, "my-listings.html");
    }
    save();
  }

  /* ---------- Looking For requests ---------- */
  const requests = () => db.requests;
  const getRequest = (id) => db.requests.find((r) => r.id === id) || null;
  const publicRequests = () => db.requests.filter((r) => (getUser(r.userId) || {}).status === "active" && !r.removed);
  function createRequest(data) {
    const me = currentUser();
    const r = { id: uid(), userId: me.id, offers: [], found: false, createdAt: now(), ...data };
    db.requests.unshift(r); save();
    return r;
  }
  function offerOnRequest(id, offer) {
    const me = currentUser(), r = getRequest(id);
    if (r.userId === me.id) return { error: "This is your own request." };
    if (r.offers.some((o) => o.userId === me.id)) return { error: "You already sent an offer." };
    r.offers.push({ id: uid(), userId: me.id, at: now(), ...offer });
    notify(r.userId, "offer", `${me.name} has “${offer.item}” for your request “${r.title}” (${rupiah(offer.price)}).`, "looking-for.html#mine");
    save();
    return { ok: true };
  }
  function withdrawRequestOffer(id) { const me = currentUser(), r = getRequest(id); r.offers = r.offers.filter((o) => o.userId !== me.id); save(); }
  function setRequestFound(id, found = true) { getRequest(id).found = found; save(); }
  function deleteRequest(id) { db.requests = db.requests.filter((r) => r.id !== id); save(); }

  /* ---------- Scam reports ---------- */
  const reports = () => db.reports;
  const getReport = (id) => db.reports.find((r) => r.id === id) || null;
  function createReport(data) {
    const me = currentUser();
    const r = { id: uid(), reporterId: me.id, status: "review", risk: "medium", seenBy: [], createdAt: now(), ...data };
    db.reports.unshift(r);
    notifyAdmins("scam", `New scam report to review: “${r.title}”`, "admin.html#reports");
    save();
    return r;
  }
  function toggleSeen(id) {
    const me = currentUser(), r = getReport(id);
    const i = r.seenBy.indexOf(me.id);
    if (i >= 0) r.seenBy.splice(i, 1); else r.seenBy.push(me.id);
    save();
    return i < 0;
  }
  function setReportStatus(id, status, reason) {
    const r = getReport(id);
    r.status = status;
    if (status === "verified") {
      r.verifiedAt = now();
      notify(r.reporterId, "approved", `Thank you! Your scam report “${r.title}” was verified and is now warning other students.`, `scam-watch.html#alert-${r.id}`);
      // warn everyone who wants scam alerts (high risk only)
      if (r.risk === "high") db.users.filter((u) => u.role === "student" && u.id !== r.reporterId && (u.prefs?.notifScam ?? true))
        .forEach((u) => notify(u.id, "scam", `Verified scam alert: ${r.title}`, `scam-watch.html#alert-${r.id}`));
      log("verify", `Scam report: ${r.title}`);
    } else if (status === "dismissed") {
      notify(r.reporterId, "info", `Your report “${r.title}” was reviewed but not published${reason ? ": " + reason : "."}`, "scam-watch.html#mine");
      log("info", `Dismissed report: ${r.title}${reason ? " — " + reason : ""}`);
    } else log("info", `Reopened report: ${r.title}`);
    save();
  }
  function setReportRisk(id, risk) { getReport(id).risk = risk; save(); }

  /* ---------- Conversations ---------- */
  const conversationsFor = (userId) => db.conversations.filter((c) => c.members.includes(userId));
  const getConversation = (id) => db.conversations.find((c) => c.id === id) || null;
  function openConversation(otherId, ref) {
    const me = currentUser();
    let c = db.conversations.find((x) => x.members.includes(me.id) && x.members.includes(otherId) && (!ref || (x.ref && x.ref.kind === ref.kind && x.ref.id === ref.id)));
    if (!c) {
      c = { id: uid(), members: [me.id, otherId], ref: ref || null, messages: [], createdAt: now() };
      db.conversations.unshift(c); save();
    }
    return c;
  }
  function sendMessage(convId, msg) {
    const me = currentUser(), c = getConversation(convId);
    const m = { id: uid(), from: me.id, at: now(), readBy: [me.id], ...msg };
    c.messages.push(m);
    c.updatedAt = m.at;
    const other = c.members.find((x) => x !== me.id);
    const preview = m.type === "meetup" ? `proposed a meet-up at ${m.place}` : m.type === "image" ? "sent a photo" : `“${String(m.text).slice(0, 60)}”`;
    if ((getUser(other)?.prefs?.notifMessages ?? true)) notify(other, "message", `${me.name} ${preview}`, `messages.html?c=${c.id}`);
    save();
    return m;
  }
  function markConversationRead(convId) {
    const me = currentUser(), c = getConversation(convId);
    let changed = false;
    c.messages.forEach((m) => { if (!m.readBy.includes(me.id)) { m.readBy.push(me.id); changed = true; } });
    if (changed) save();
  }
  function respondMeetup(convId, msgId, accept) {
    const c = getConversation(convId), m = c.messages.find((x) => x.id === msgId);
    m.status = accept ? "accepted" : "declined";
    save();
    sendMessage(convId, { text: accept ? `✅ Meet-up accepted: ${m.place}, ${m.date} ${m.time}` : "❌ I can't make that meet-up. Can we pick another time?" });
  }
  const unreadMessages = (userId) => conversationsFor(userId).reduce((s, c) => s + c.messages.filter((m) => !m.readBy.includes(userId)).length, 0);

  /* ---------- Image helper: shrink photos so they fit in browser storage ---------- */
  function compressImage(fileOrUrl, maxSize = 720, quality = 0.72) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      const url = typeof fileOrUrl === "string" ? fileOrUrl : URL.createObjectURL(fileOrUrl);
      img.onload = () => {
        const scale = Math.min(1, maxSize / Math.max(img.width, img.height));
        const c = document.createElement("canvas");
        c.width = Math.round(img.width * scale); c.height = Math.round(img.height * scale);
        c.getContext("2d").drawImage(img, 0, 0, c.width, c.height);
        if (typeof fileOrUrl !== "string") URL.revokeObjectURL(url);
        resolve(c.toDataURL("image/jpeg", quality));
      };
      img.onerror = () => reject(new Error("Couldn't read that image."));
      img.src = url;
    });
  }

  /* ---------- Formatting (shared) ---------- */
  function rupiah(n) { return "Rp " + Number(n || 0).toLocaleString("id-ID"); }

  // Close finished auctions whenever a page loads
  finalizeAuctions();

  return {
    ready, save, onChange, uid,
    // users
    users, getUser, findByEmail, currentUser, register, login, loginWithUGM, logout, updateUser, deleteUser, changePassword, setUserStatus, admins,
    // wishlist
    isSaved, toggleSaved,
    // notifications
    notify, notificationsFor, unreadCount, markNotification, markAllNotifications, deleteNotification,
    // listings & auctions
    listings, getListing, publicListings, isPublic, createListing, updateListing, deleteListing, moderate, addView,
    highestBid, currentPrice, increment, minNextBid, auctionEnded, placeBid, buyNow, finalizeAuctions,
    makeBarterOffer, cancelBarterOffer, respondBarterOffer,
    // requests
    requests, getRequest, publicRequests, createRequest, offerOnRequest, withdrawRequestOffer, setRequestFound, deleteRequest,
    // reports
    reports, getReport, createReport, toggleSeen, setReportStatus, setReportRisk,
    // chat
    conversationsFor, getConversation, openConversation, sendMessage, markConversationRead, respondMeetup, unreadMessages,
    // admin log
    log, adminLog: () => db.adminLog,
    // helpers
    compressImage, rupiah,
  };
})();
