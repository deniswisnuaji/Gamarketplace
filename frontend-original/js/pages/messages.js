document.addEventListener("DOMContentLoaded", () => {
  /* ============ MESSAGES ============ */
  // Real chats between accounts (Store). Open a chat from an item: messages.html?to=<userId>&listing=<id>
  if (!requirePage()) return;
  const QUICK = ["Masih ada?", "Bisa nego?", "Bisa COD di fakultasku?", "Kondisinya gimana?", "Oke, deal!"];
  const view = { filter: "all", search: "", activeId: null };
  const chat = $("#chat");
  const meId = () => me().id;
  const otherOf = (c) => Store.getUser(c.members.find((x) => x !== meId())) || { id: "", name: "Deleted user", faculty: "" };
  const lastMsg = (c) => c.messages[c.messages.length - 1];
  const unreadIn = (c) => c.messages.filter((m) => !m.readBy.includes(meId())).length;
  function refInfo(c) {
    if (!c.ref) return null;
    if (c.ref.kind === "listing") {
      const l = Store.getListing(c.ref.id);
      if (!l) return { title: "Item removed", sub: "", img: "", href: "#", role: "" };
      return {
        title: l.title, img: listingPhoto(l), href: `item.html?id=${l.id}`,
        sub: l.type === "barter" ? "Barter" : l.type === "auction" ? `Auction · ${rupiah(Store.currentPrice(l))}` : rupiah(l.price),
        role: l.sellerId === meId() ? "selling" : "buying",
      };
    }
    const r = Store.getRequest(c.ref.id);
    if (!r) return { title: "Request removed", sub: "", img: "", href: "#", role: "" };
    return { title: `Looking for: ${r.title}`, img: (r.photos || [])[0] || "", href: "looking-for.html", sub: `Budget up to ${rupiah(r.max)}`, role: r.userId === meId() ? "buying" : "selling" };
  }
  const fmtTime = (t) => new Date(t).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
  function dayLabel(t) {
    const d = new Date(t), today = new Date();
    const diff = Math.round((new Date(today.toDateString()) - new Date(d.toDateString())) / 86400e3);
    return diff === 0 ? "Today" : diff === 1 ? "Yesterday" : d.toLocaleDateString("en-GB", { day: "numeric", month: "short" });
  }
  const listTime = (t) => (dayLabel(t) === "Today" ? fmtTime(t) : dayLabel(t));
  function preview(c) {
    const m = lastMsg(c);
    if (!m) return "No messages yet";
    const p = m.from === meId() ? "You: " : "";
    return p + (m.type === "meetup" ? "📍 Meet-up proposal" : m.type === "image" ? "📷 Photo" : m.text);
  }

  /* ---- Conversation list ---- */
  function renderList() {
    const list = Store.conversationsFor(meId())
      .filter((c) => c.messages.length || c.id === view.activeId)
      .filter((c) => { const r = refInfo(c); return view.filter === "all" || (view.filter === "unread" ? unreadIn(c) > 0 : r && r.role === view.filter); })
      .filter((c) => !view.search || `${otherOf(c).name} ${refInfo(c)?.title || ""}`.toLowerCase().includes(view.search))
      .sort((a, b) => (b.updatedAt || b.createdAt) - (a.updatedAt || a.createdAt));
    $("#convoList").innerHTML = list.length ? list.map((c) => {
      const o = otherOf(c), r = refInfo(c), n = unreadIn(c);
      return `
      <li>
        <button type="button" class="convo${c.id === view.activeId ? " is-active" : ""}${n ? " is-unread" : ""}" data-id="${c.id}" aria-current="${c.id === view.activeId}">
          ${avatarHTML(o, 46).replace('class="avatar-lg"', 'class="avatar-lg convo__avatar"')}
          <span class="convo__body">
            <span class="convo__top"><strong>${esc(o.name)}</strong><time>${listTime(c.updatedAt || c.createdAt)}</time></span>
            ${r ? `<span class="convo__item">${r.role === "selling" ? "Selling" : "Buying"} · ${esc(r.title)}</span>` : ""}
            <span class="convo__preview"><span>${esc(preview(c))}</span>${n ? `<b class="unread-badge" aria-label="${n} unread">${n}</b>` : ""}</span>
          </span>
        </button>
      </li>`;
    }).join("") : `<li class="convo-empty">${Store.conversationsFor(meId()).some((c) => c.messages.length) ? "No chats found." : "No chats yet.<br />Open an item and tap “Chat seller” to start one."}</li>`;
  }

  /* ---- Thread ---- */
  function messageHTML(c, m) {
    const mine = m.from === meId();
    const read = mine && c.members.every((id) => m.readBy.includes(id));
    const ticks = mine ? `<i data-lucide="${read ? "check-check" : "check"}" class="${read ? "is-read" : ""}"></i>` : "";
    let inner;
    if (m.type === "meetup") {
      const d = new Date(`${m.date}T${m.time}`);
      const pending = !m.status || m.status === "pending";
      inner = `<div class="meet-card">
        <div class="meet-card__title"><i data-lucide="map-pin"></i> Meet-up proposal</div>
        <strong>${esc(m.place)}</strong>
        <span>${d.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short" })} · ${esc(m.time)}</span>
        <p class="meet-card__status ${m.status === "accepted" ? "is-accepted" : "is-pending"}">${m.status === "accepted" ? "✓ Accepted" : m.status === "declined" ? "✗ Declined" : mine ? "Waiting for reply…" : "Do you agree?"}</p>
        ${pending && !mine ? `<div class="meet-card__actions"><button type="button" class="btn btn--primary btn--sm btn--keep" data-meet="${m.id}:1"><span>Accept</span></button><button type="button" class="btn btn--outline btn--sm" data-meet="${m.id}:0">Decline</button></div>` : ""}
        <a href="meetup-map.html#${encodeURIComponent(m.place.toLowerCase())}">See on meet-up map →</a>
      </div>`;
    } else if (m.type === "image") {
      inner = `<div class="bubble"><img src="${m.src}" alt="Photo" /></div>`;
    } else {
      inner = `<div class="bubble">${esc(m.text)}</div>`;
    }
    return `<li class="msg msg--${mine ? "me" : "them"}">${inner}<span class="msg__time">${fmtTime(m.at)} ${ticks}</span></li>`;
  }
  function renderThread() {
    const c = Store.getConversation(view.activeId);
    $("#threadEmpty").hidden = !!c;
    $("#threadInner").hidden = !c;
    if (!c) return;
    const o = otherOf(c), r = refInfo(c);
    $("#tAvatar").outerHTML = avatarHTML(o, 42).replace('class="avatar-lg"', 'class="avatar-lg thread__avatar" id="tAvatar"');
    $("#tName").textContent = o.name;
    $("#tStatus").textContent = o.status === "suspended" ? "Account suspended" : o.faculty ? `${o.faculty} · UGM student` : "UGM student";
    $("#tProfile").href = `profile.html?u=${o.id}`;
    const ctx = $("#tItem");
    ctx.hidden = !r;
    if (r) {
      ctx.href = r.href;
      $("#tItemImg").src = r.img || ""; $("#tItemImg").hidden = !r.img;
      $("#tItemTitle").textContent = r.title;
      $("#tItemPrice").textContent = r.sub;
      $("#tRole").textContent = r.role === "selling" ? "You're selling" : "You're buying";
    }
    let html = "", last = "";
    c.messages.forEach((m) => { const d = dayLabel(m.at); if (d !== last) { html += `<li class="day-sep">${d}</li>`; last = d; } html += messageHTML(c, m); });
    if (!c.messages.length) html = `<li class="day-sep">Say hi to ${esc(o.name)} 👋</li>`;
    const box = $("#messages");
    box.innerHTML = html;
    box.scrollTop = box.scrollHeight;
    $("#quickReplies").innerHTML = QUICK.map((q) => `<button type="button" data-quick="${esc(q)}">${esc(q)}</button>`).join("");
    const blocked = o.status !== "active";
    $("#msgInput").disabled = blocked; $("#meetupBtn").disabled = blocked;
    $("#msgInput").placeholder = blocked ? "You can't reply to this account." : "Write a message…";
    Store.markConversationRead(c.id);
    icons();
  }
  function openConvo(id) {
    view.activeId = id;
    chat.classList.add("is-thread");
    history.replaceState(null, "", `messages.html?c=${id}`);
    renderList();
    renderThread();
    renderSessionUI();
    if (window.matchMedia("(min-width: 861px)").matches) $("#msgInput").focus();
  }

  /* ---- Sending ---- */
  function send(msg) {
    if (!view.activeId) return;
    try { Store.sendMessage(view.activeId, msg); } catch (e) { return toast(e.message); }
    renderList();
    renderThread();
  }
  const input = $("#msgInput");
  input.addEventListener("input", () => {
    $("#sendBtn").disabled = !input.value.trim();
    input.style.height = "auto";
    input.style.height = Math.min(input.scrollHeight, 140) + "px";
  });
  input.addEventListener("keydown", (e) => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); $("#composer").requestSubmit(); } });
  $("#composer").addEventListener("submit", (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;
    if (/\b(otp|kode verifikasi|password|pin)\b/i.test(text)) toast("Careful: never share OTP codes, PINs or passwords in chat.");
    send({ text });
    input.value = ""; input.style.height = "auto";
    $("#sendBtn").disabled = true;
  });
  $("#quickReplies").addEventListener("click", (e) => { const b = e.target.closest("[data-quick]"); if (b) send({ text: b.dataset.quick }); });
  $("#attachInput").addEventListener("change", async (e) => {
    const file = e.target.files[0];
    e.target.value = "";
    if (!file) return;
    if (!file.type.startsWith("image/")) return toast("Only photos can be sent.");
    try { send({ type: "image", src: await Store.compressImage(file, 800, 0.7) }); } catch (err) { toast(err.message); }
  });
  $("#messages").addEventListener("click", (e) => {
    const b = e.target.closest("[data-meet]");
    if (!b) return;
    const [id, yes] = b.dataset.meet.split(":");
    Store.respondMeetup(view.activeId, id, yes === "1");
    renderList(); renderThread();
  });

  /* ---- Meet-up proposal ---- */
  const muDialog = $("#meetupDialog");
  $("#meetupBtn").addEventListener("click", () => {
    const tomorrow = new Date(Date.now() + 86400e3);
    $("#muDate").min = new Date().toISOString().slice(0, 10);
    $("#muDate").value = tomorrow.toISOString().slice(0, 10);
    const c = Store.getConversation(view.activeId);
    const l = c && c.ref && c.ref.kind === "listing" ? Store.getListing(c.ref.id) : null;
    $("#muPlace").value = (l && l.tikum) || me().tikum || "FEB";
    $("#muError").hidden = true;
    muDialog.showModal();
  });
  $("#meetupForm").addEventListener("submit", (e) => {
    e.preventDefault();
    const date = $("#muDate").value, time = $("#muTime").value;
    const fail = (m) => { $("#muError").textContent = m; $("#muError").hidden = false; };
    if (!date || !time) return fail("Choose a date and time.");
    if (new Date(`${date}T${time}`) < new Date()) return fail("That time has already passed.");
    muDialog.close();
    send({ type: "meetup", place: $("#muPlace").value, date, time, status: "pending" });
  });

  /* ---- List controls ---- */
  $("#convoList").addEventListener("click", (e) => { const b = e.target.closest(".convo"); if (b) openConvo(b.dataset.id); });
  $("#chatSearch").addEventListener("input", (e) => { view.search = e.target.value.trim().toLowerCase(); renderList(); });
  $$(".chip[data-filter]").forEach((b) => b.addEventListener("click", () => {
    view.filter = b.dataset.filter;
    $$(".chip[data-filter]").forEach((x) => { x.classList.toggle("is-active", x === b); x.setAttribute("aria-pressed", String(x === b)); });
    renderList();
  }));
  $("#backBtn").addEventListener("click", () => { chat.classList.remove("is-thread"); view.activeId = null; history.replaceState(null, "", "messages.html"); renderList(); renderThread(); });
  $("#reportBtn").addEventListener("click", () => { const c = Store.getConversation(view.activeId); location.href = `scam-watch.html?report=1&about=${encodeURIComponent("chat with " + otherOf(c).name)}`; });

  /* ---- Start ---- */
  const p = new URLSearchParams(location.search);
  renderList();
  if (p.get("to")) {
    const other = Store.getUser(p.get("to"));
    if (!other || other.id === meId()) { toast("That chat isn't available."); renderThread(); }
    else {
      const ref = p.get("listing") ? { kind: "listing", id: p.get("listing") } : p.get("request") ? { kind: "request", id: p.get("request") } : null;
      openConvo(Store.openConversation(other.id, ref).id);
    }
  } else if (p.get("c") && Store.getConversation(p.get("c"))) {
    openConvo(p.get("c"));
  } else {
    const first = Store.conversationsFor(meId()).filter((c) => c.messages.length).sort((a, b) => (b.updatedAt || 0) - (a.updatedAt || 0))[0];
    if (first && window.matchMedia("(min-width: 861px)").matches) openConvo(first.id); else renderThread();
  }
  // New messages from another tab / account show up right away
  Store.onChange(() => { renderList(); if (view.activeId) renderThread(); });
});
