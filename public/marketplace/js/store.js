/* GaMarketplace: server-backed data adapter.
 * SQL and the Laravel session are authoritative. This in-memory projection
 * contains only data the server has authorized for the current visitor.
 */
const Store = (() => {
  const empty = () => ({ users: [], listings: [], requests: [], reports: [], conversations: [], notifications: [], adminLog: [], currentUserId: null });
  let db = { ...empty(), ...(window.GM_BOOTSTRAP || {}) };
  const listeners = new Set();
  let pendingWrites = 0, refreshing = false, writeQueue = Promise.resolve();
  const ready = Promise.resolve();
  const onChange = (fn) => { listeners.add(fn); return () => listeners.delete(fn); };
  const emit = () => listeners.forEach((fn) => { try { Promise.resolve(fn()).catch(showError); } catch (error) { showError(error); } });
  function showError(error) {
    if (typeof toast === "function") toast(error.message || "The request could not be completed. Please try again.");
  }
  class RequestError extends Error {}
  window.addEventListener("unhandledrejection", (event) => {
    if (event.reason instanceof RequestError) { event.preventDefault(); showError(event.reason); }
  });
  function accept(payload) {
    if (payload.csrf) document.querySelector('meta[name="csrf-token"]')?.setAttribute("content", payload.csrf);
    if (payload.state && JSON.stringify(db) !== JSON.stringify({ ...empty(), ...payload.state })) { db = { ...empty(), ...payload.state }; emit(); }
    return payload.result;
  }
  async function request(path, data, method = "POST") {
    let response;
    try {
      response = await fetch(path, {
        method, credentials: "same-origin", cache: "no-store",
        headers: { "Accept": "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "" },
        ...(method === "GET" ? {} : { body: JSON.stringify(data || {}) }),
      });
    } catch { throw new RequestError("Connection failed. Your change was not confirmed. Please try again."); }
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      if (response.status === 401 || response.status === 419) throw new RequestError("Your session has expired. Reload the page and sign in again.");
      const detail = Object.values(payload.errors || {}).flat()[0];
      throw new RequestError(detail || payload.message || "The request could not be completed.");
    }
    return accept(payload);
  }
  function write(path, data) {
    pendingWrites++;
    const task = writeQueue.then(() => request(path, data));
    writeQueue = task.catch(() => {}).finally(() => { pendingWrites--; });
    return task;
  }
  const action = (name, data) => write(`/gm/api/action/${name}`, data);
  async function refresh() {
    if (refreshing || pendingWrites || document.hidden || document.activeElement?.matches("input, textarea, select") || document.querySelector("dialog[open]")) return;
    refreshing = true;
    try { await request("/gm/api/state", null, "GET"); } finally { refreshing = false; }
  }
  setInterval(() => refresh().catch(() => {}), 15000);
  window.addEventListener("focus", () => refresh().catch(() => {}));
  const users = () => db.users;
  const getUser = (id) => db.users.find((u) => u.id === String(id)) || null;
  const currentUser = () => getUser(db.currentUserId);
  const admins = () => db.users.filter((u) => u.role === "admin");
  const findByEmail = (email) => db.users.find((u) => u.email && u.email.toLowerCase() === String(email).toLowerCase()) || null;
  async function auth(path, data) { try { return await write(path, data); } catch (error) { return { error: error.message }; } }
  const login = (email, password, { admin = false, remember = true } = {}) => auth("/gm/api/login", { email, password, admin, remember });
  const register = (data) => auth("/gm/api/register", data);
  const logout = () => write("/gm/api/logout");
  const updateUser = (id, patch) => write("/gm/api/profile", { id, patch });
  const changePassword = (userId, oldPw, newPw, newPw_confirmation) => auth("/gm/api/password", { oldPw, newPw, newPw_confirmation });
  const setUserStatus = (id, status, reason = "") => action("setUserStatus", { id, status, reason });
  const isSaved = (userId, kind, id) => (getUser(userId)?.wishlist || []).some((w) => w.kind === kind && w.id === id);
  const toggleSaved = (userId, kind, id) => action("toggleSaved", { userId, kind, id });
  const notificationsFor = (userId) => db.notifications.filter((n) => n.userId === userId);
  const unreadCount = (userId) => notificationsFor(userId).filter((n) => !n.read).length;
  const markNotification = (id, read = true) => action("markNotification", { id, read });
  const markAllNotifications = (userId) => action("markAllNotifications", { userId });
  const deleteNotification = (id) => action("deleteNotification", { id });
  const listings = () => db.listings;
  const getListing = (id) => db.listings.find((l) => l.id === String(id)) || null;
  const isPublic = (l) => !!l && ["marketplace", "auction"].includes(l.type) && l.moderation === "approved" && getUser(l.sellerId)?.status === "active";
  const publicListings = (type) => db.listings.filter((l) => isPublic(l) && (!type || l.type === type));
  const createListing = (data) => action("createListing", { data });
  const updateListing = (id, patch, { resubmit = false } = {}) => action("updateListing", { id, patch, resubmit });
  const deleteListing = (id, reason = "") => action("deleteListing", { id, reason });
  const moderate = (id, decision, reason = "") => action("moderate", { id, decision, reason });
  const addView = (id) => action("addView", { id });
  const highestBid = (l) => l?.auction?.bids?.at(-1) || null;
  const currentPrice = (l) => highestBid(l)?.amount ?? l.price;
  const increment = (price) => price < 100000 ? 5000 : price < 1000000 ? 25000 : 50000;
  const minNextBid = (l) => highestBid(l) ? currentPrice(l) + increment(currentPrice(l)) : l.price;
  const auctionEnded = (l) => !!l?.auction?.endsAt && (l.auction.endsAt <= Date.now() || l.auction.finalized);
  const placeBid = (id, amount) => action("placeBid", { id, amount });
  const buyNow = (id) => action("buyNow", { id });
  // Closing auctions is a server job; this only refreshes the projection.
  const finalizeAuctions = () => refresh().catch(() => {});
  const requests = () => db.requests;
  const getRequest = (id) => db.requests.find((r) => r.id === String(id)) || null;
  const publicRequests = () => db.requests.filter((r) => getUser(r.userId)?.status === "active" && !r.removed);
  const createRequest = (data) => action("createRequest", { data });
  const offerOnRequest = (id, offer) => action("offerOnRequest", { id, offer });
  const withdrawRequestOffer = (id) => action("withdrawRequestOffer", { id });
  const setRequestFound = (id, found = true) => action("setRequestFound", { id, found });
  const deleteRequest = (id, reason = "") => action("deleteRequest", { id, reason });
  const reports = () => db.reports;
  const getReport = (id) => db.reports.find((r) => r.id === String(id)) || null;
  const createReport = (data) => action("createReport", { data });
  const toggleSeen = (id) => action("toggleSeen", { id });
  const setReportStatus = (id, status, reason = "", publication = {}) => action("setReportStatus", { id, status, reason, ...publication });
  const setReportRisk = (id, risk) => action("setReportRisk", { id, risk });
  const conversationsFor = (userId) => db.conversations.filter((c) => c.members.includes(userId));
  const getConversation = (id) => db.conversations.find((c) => c.id === String(id)) || null;
  const openConversation = (otherId, ref) => action("openConversation", { otherId, ref });
  const sendMessage = (convId, msg) => action("sendMessage", { convId, msg });
  const reading = new Set();
  async function markConversationRead(convId) {
    const c = getConversation(convId), user = currentUser();
    if (!user || !c || reading.has(convId) || !c.messages.some((m) => !m.readBy.includes(user.id))) return;
    reading.add(convId);
    try { return await action("markConversationRead", { convId }); } finally { reading.delete(convId); }
  }
  const respondMeetup = (convId, msgId, accept) => action("respondMeetup", { convId, msgId, accept });
  const unreadMessages = (userId) => conversationsFor(userId).reduce((sum, c) => sum + c.messages.filter((m) => !m.readBy.includes(userId)).length, 0);
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


  const rupiah = (n) => "Rp " + Number(n || 0).toLocaleString("id-ID");
  return {
    ready, onChange, refresh, users, getUser, currentUser, admins, findByEmail,
    login, register, logout, updateUser, changePassword, setUserStatus,
    isSaved, toggleSaved, notificationsFor, unreadCount, markNotification, markAllNotifications, deleteNotification,
    listings, getListing, isPublic, publicListings, createListing, updateListing, deleteListing, moderate, addView,
    highestBid, currentPrice, increment, minNextBid, auctionEnded, placeBid, buyNow, finalizeAuctions,
    requests, getRequest, publicRequests, createRequest, offerOnRequest, withdrawRequestOffer, setRequestFound, deleteRequest,
    reports, getReport, createReport, toggleSeen, setReportStatus, setReportRisk,
    conversationsFor, getConversation, openConversation, sendMessage, markConversationRead, respondMeetup, unreadMessages,
    adminLog: () => db.adminLog, compressImage, rupiah,
  };
})();
