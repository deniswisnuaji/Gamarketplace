/* GaMarketplace — sidebar (slide open/close), profile menu and Esc key. Used by every page with the sidebar. */
document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) lucide.createIcons();

  /* ============ SIDEBAR (slide-left close / open) ============ */
  const body = document.body;
  const sidebar = document.getElementById("sidebar");
  const openBtn = document.getElementById("sidebarOpen");
  const closeBtn = document.getElementById("sidebarClose");
  const overlay = document.getElementById("sidebarOverlay");
  const mobileQuery = window.matchMedia("(max-width: 1024px)");

  // Desktop: sidebar starts open (remembered). Mobile: starts closed.
  function getSavedState() {
    try { return localStorage.getItem("gm-sidebar"); } catch { return null; }
  }
  function saveState(state) {
    try { localStorage.setItem("gm-sidebar", state); } catch { /* ignore */ }
  }

  function setSidebar(open, { remember = true } = {}) {
    body.classList.toggle("sidebar-collapsed", !open);
    openBtn.setAttribute("aria-expanded", String(open));
    sidebar.setAttribute("aria-hidden", String(!open));
    sidebar.inert = !open;

    const showOverlay = open && mobileQuery.matches;
    overlay.hidden = !showOverlay;
    body.classList.toggle("no-scroll", showOverlay);

    if (remember && !mobileQuery.matches) saveState(open ? "open" : "closed");
  }

  function initSidebar() {
    // Disable animation for the very first paint so it doesn't slide on load
    body.classList.add("no-transition");
    const open = mobileQuery.matches ? false : getSavedState() !== "closed";
    setSidebar(open, { remember: false });
    requestAnimationFrame(() => requestAnimationFrame(() => body.classList.remove("no-transition")));
  }

  closeBtn.addEventListener("click", () => {
    setSidebar(false);
    openBtn.focus();
  });
  openBtn.addEventListener("click", () => {
    const isOpen = !body.classList.contains("sidebar-collapsed");
    setSidebar(!isOpen);
    if (!isOpen) closeBtn.focus();
  });
  overlay.addEventListener("click", () => setSidebar(false));
  mobileQuery.addEventListener("change", initSidebar);

  initSidebar();

  /* ============ PROFILE DROPDOWN ============ */
  const profileToggle = document.getElementById("profileToggle");
  const profileMenu = document.getElementById("profileMenu");

  function setProfileMenu(open) {
    profileMenu.hidden = !open;
    profileToggle.setAttribute("aria-expanded", String(open));
  }
  profileToggle.addEventListener("click", (e) => {
    e.stopPropagation();
    setProfileMenu(profileMenu.hidden);
  });
  document.addEventListener("click", (e) => {
    if (!profileMenu.hidden && !profileMenu.contains(e.target)) setProfileMenu(false);
  });

  /* ============ ESC closes things ============ */
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    if (!profileMenu.hidden) { setProfileMenu(false); profileToggle.focus(); }
    else if (mobileQuery.matches && !body.classList.contains("sidebar-collapsed")) setSidebar(false);
  });
});
