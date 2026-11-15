/* ============ LOG OUT ============ */
// In Laravel, logging out must be a POST form to the logout route with @csrf.
document.addEventListener("DOMContentLoaded", () => {
  if (window.lucide) lucide.createIcons();
  const user = Store.currentUser();
  const wasAdmin = user && user.role === "admin";
  if (!user) {
    $("#confirmCard").hidden = true;
    $("#doneCard").hidden = false;
    $("#doneTitle").textContent = "You're not logged in";
  }
  $("#logoutBtn").addEventListener("click", () => {
    Store.logout();
    if ($("#clearData").checked) {
      try { ["gm-sidebar", "gm-listing-draft"].forEach((k) => localStorage.removeItem(k)); sessionStorage.clear(); } catch (e) {}
    }
    $("#confirmCard").hidden = true;
    $("#doneCard").hidden = false;
    if (wasAdmin) $("#doneCard .btn").setAttribute("href", "admin-login.html");
    $("#doneTitle").focus();
  });
  $("#cancelBtn").addEventListener("click", () => { if (history.length > 1) history.back(); else location.href = "main.html"; });
});
