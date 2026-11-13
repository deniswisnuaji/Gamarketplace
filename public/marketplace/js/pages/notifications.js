document.addEventListener("DOMContentLoaded", () => {
  /* ============ NOTIFICATIONS ============ */
  // Real notifications created by Store when something happens (offers, bids, approvals, messages…).
  if (!requirePage()) return;
  const ICON = { review: "clipboard-check", approved: "circle-check", rejected: "circle-x", offer: "arrow-left-right", outbid: "trending-up", won: "trophy", message: "message-circle-more", scam: "shield-alert", drop: "tag", info: "info" };
  const STYLE = { review: "meetup", approved: "approved", rejected: "rejected", offer: "offer", outbid: "outbid", won: "won", message: "message", scam: "scam", drop: "drop", info: "message" };
  const GROUP = { outbid: "bidding", won: "bidding", scam: "safety" };
  const view = { tab: "all" };
  const groupOf = (t) => { const d = new Date(t), today = new Date(); const diff = Math.round((new Date(today.toDateString()) - new Date(d.toDateString())) / 86400e3); return diff === 0 ? "Today" : diff === 1 ? "Yesterday" : "Earlier"; };

  function render() {
    const all = Store.notificationsFor(me().id);
    const unread = all.filter((n) => !n.read).length;
    $("#unreadText").textContent = `${unread} unread`;
    $('[data-count="unread"]').textContent = unread;
    $("#markAllBtn").disabled = unread === 0;
    const list = all.filter((n) => view.tab === "all" || (view.tab === "unread" ? !n.read : (GROUP[n.type] || "trade") === view.tab));
    const groups = {};
    list.forEach((n) => (groups[groupOf(n.at)] ||= []).push(n));
    $("#notifGroups").innerHTML = Object.entries(groups).map(([g, items]) => `
      <section class="notif-group" aria-label="${g}">
        <h2>${g}</h2>
        ${items.map((n) => `
          <article class="notif${n.read ? "" : " is-unread"}">
            <span class="notif__icon notif__icon--${STYLE[n.type] || "message"}" aria-hidden="true"><i data-lucide="${ICON[n.type] || "bell"}"></i></span>
            <div class="notif__body">
              ${n.link ? `<a class="notif__link" href="${esc(n.link)}" data-open="${n.id}"><p class="notif__text">${esc(n.text)}</p></a>` : `<p class="notif__text">${esc(n.text)}</p>`}
              <p class="notif__meta">${n.read ? "" : "<span class='sr-only'>Unread. </span>"}${timeAgo(n.at)}</p>
              ${n.read ? "" : `<div class="notif__actions"><button type="button" class="btn btn--outline btn--sm" data-read="${n.id}">Mark as read</button></div>`}
            </div>
            <button type="button" class="notif__del" data-del="${n.id}" aria-label="Delete notification"><i data-lucide="x"></i></button>
          </article>`).join("")}
      </section>`).join("");
    $("#emptyState").hidden = list.length > 0;
    document.title = (unread ? `(${unread}) ` : "") + "Notifications — GaMarketplace";
    renderSessionUI();
    icons();
  }

  $$(".status-tab").forEach((t) => t.addEventListener("click", () => {
    view.tab = t.dataset.tab;
    $$(".status-tab").forEach((x) => { x.classList.toggle("is-active", x === t); x.setAttribute("aria-selected", String(x === t)); });
    render();
  }));
  $("#notifGroups").addEventListener("click", (e) => {
    const open = e.target.closest("[data-open]");
    if (open) { Store.markNotification(open.dataset.open, true); return; } // the link continues
    const read = e.target.closest("[data-read]");
    if (read) { Store.markNotification(read.dataset.read, true); render(); return; }
    const del = e.target.closest("[data-del]");
    if (del) { Store.deleteNotification(del.dataset.del); render(); toast("Notification deleted."); }
  });
  $("#markAllBtn").addEventListener("click", () => { Store.markAllNotifications(me().id); render(); toast("All notifications marked as read."); });

  // Quick settings (saved on the user, same as Settings → Notifications)
  $$("[data-pref]").forEach((cb) => {
    cb.checked = (me().prefs || {})[cb.dataset.pref] !== false;
    cb.addEventListener("change", () => { Store.updateUser(me().id, { prefs: { ...(me().prefs || {}), [cb.dataset.pref]: cb.checked } }); toast("Saved."); });
  });

  render();
  Store.onChange(render);
});
