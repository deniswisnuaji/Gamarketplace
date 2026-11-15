{{-- Converted from frontend-original/messages.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Messages — GaMarketplace')

@section('description', 'Chat with buyers and sellers on GaMarketplace.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/messages.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/messages.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CHAT ---------- -->
  <div class="chat" id="chat">
    <!-- Conversation list -->
    <aside class="chat-list" aria-label="Conversations">
      <div class="chat-list__head">
        <h1>Messages</h1>
        <label class="field field--search chat-list__search">
          <i data-lucide="search"></i>
          <span class="sr-only">Search chats</span>
          <input type="search" id="chatSearch" placeholder="Search chats" autocomplete="off" />
        </label>
        <div class="chip-row" role="group" aria-label="Filter chats">
          <button type="button" class="chip is-active" data-filter="all" aria-pressed="true">All</button>
          <button type="button" class="chip" data-filter="unread" aria-pressed="false">Unread</button>
          <button type="button" class="chip" data-filter="buying" aria-pressed="false">Buying</button>
          <button type="button" class="chip" data-filter="selling" aria-pressed="false">Selling</button>
        </div>
      </div>
      <ul class="convo-list" id="convoList"></ul>
    </aside>

    <!-- Thread -->
    <section class="thread" id="thread" aria-label="Conversation">
      <div class="thread__empty" id="threadEmpty">
        <i data-lucide="message-circle-more"></i>
        <p><strong>Select a chat</strong><br />Pick a conversation on the left to start talking.</p>
      </div>

      <div class="thread__inner" id="threadInner" hidden>
        <header class="thread__head">
          <button type="button" class="icon-btn thread__back" id="backBtn" aria-label="Back to chats"><i data-lucide="arrow-left"></i></button>
          <span class="avatar-lg thread__avatar" id="tAvatar" aria-hidden="true"></span>
          <div class="thread__who">
            <strong id="tName"></strong>
            <span id="tStatus"></span>
          </div>
          <a href="profile.html" class="icon-btn" id="tProfile" aria-label="View profile"><i data-lucide="user-round"></i></a>
          <button type="button" class="icon-btn" id="reportBtn" aria-label="Report this user"><i data-lucide="flag"></i></button>
        </header>

        <a class="item-ctx" id="tItem" href="marketplace.html">
          <img id="tItemImg" src="" alt="" />
          <div><strong id="tItemTitle"></strong><span id="tItemPrice"></span></div>
          <span class="item-ctx__role" id="tRole"></span>
        </a>

        <p class="safety-note"><i data-lucide="shield-check"></i> Keep chats here. Never share OTP codes, and check transfers in your own banking app.</p>

        <ol class="messages" id="messages" aria-live="polite"></ol>

        <div class="quick-replies" id="quickReplies"></div>

        <form class="composer" id="composer">
          <label class="icon-btn composer__attach" for="attachInput" aria-label="Attach a photo"><i data-lucide="image-plus"></i></label>
          <input type="file" id="attachInput" class="sr-only" accept="image/*" />
          <button type="button" class="icon-btn" id="meetupBtn" aria-label="Propose a meet-up"><i data-lucide="calendar-plus"></i></button>
          <label for="msgInput" class="sr-only">Message</label>
          <textarea id="msgInput" rows="1" placeholder="Write a message…" maxlength="1000"></textarea>
          <button type="submit" class="composer__send" id="sendBtn" aria-label="Send" disabled><i data-lucide="send"></i></button>
        </form>
      </div>
    </section>
  </div>

<!-- Meet-up proposal dialog -->
<dialog class="bid-dialog form-dialog" id="meetupDialog" aria-labelledby="muTitle">
  <form method="dialog" class="form-dialog__inner" id="meetupForm" novalidate>
    <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close"><i data-lucide="x"></i></button>
    <h2 id="muTitle">Propose a meet-up</h2>
    <p class="form-dialog__sub">Pick a public spot on campus. They can accept or suggest another time.</p>
    <label class="bx-label" for="muPlace">Meet-up point (tikum)</label>
    <select id="muPlace" class="select"><option value="Biologi">Biologi</option><option value="FEB">FEB</option><option value="Farmasi">Farmasi</option><option value="Filsafat">Filsafat</option><option value="Geografi">Geografi</option><option value="FH">FH</option><option value="FIB">FIB</option><option value="FISIPOL">FISIPOL</option><option value="FK-KMK">FK-KMK</option><option value="FKG">FKG</option><option value="FKH">FKH</option><option value="FKT">FKT</option><option value="FMIPA">FMIPA</option><option value="FAPERTA">FAPERTA</option><option value="FAPET">FAPET</option><option value="Psikologi">Psikologi</option><option value="FT">FT</option><option value="FTP">FTP</option><option value="SV">SV</option><option value="SPs">SPs</option></select>
    <div class="two-col">
      <div><label class="bx-label" for="muDate">Date</label><input type="date" id="muDate" class="text-input" /></div>
      <div><label class="bx-label" for="muTime">Time</label><input type="time" id="muTime" class="text-input" value="13:00" /></div>
    </div>
    <p class="form-error" id="muError" role="alert" hidden></p>
    <div class="bid-dialog__actions">
      <button type="button" class="btn btn--outline" data-close>Cancel</button>
      <button type="submit" class="btn btn--primary"><span>Send proposal</span></button>
    </div>
  </form>
</dialog>
@endsection
