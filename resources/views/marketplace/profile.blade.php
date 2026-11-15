{{-- Converted from frontend-original/profile.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'My Profile — GaMarketplace')

@section('description', 'Your GaMarketplace profile, listings and reviews.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/profile.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/profile.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT GRID ---------- -->
  <div class="layout">
    <main class="main" id="main">
      <!-- Profile header -->
      <section class="profile-card">
        <div class="profile-card__cover" aria-hidden="true"></div>
        <div class="profile-card__body">
          <div class="avatar-lg profile-card__avatar" id="pAvatar" aria-hidden="true"></div>
          <div class="profile-card__info">
            <h1 class="profile-card__name"><span id="pName"></span>
              <span class="verified-pill"><i data-lucide="badge-check"></i> UGM account</span>
            </h1>
            <p class="profile-card__handle">@<span id="pUsername"></span></p>
            <p class="profile-card__bio" id="pBio"></p>
            <ul class="profile-card__facts">
              <li id="pFacultyRow"><i data-lucide="graduation-cap"></i><span id="pFaculty"></span></li>
              <li><i data-lucide="map-pin"></i>Usual tikum: <strong id="pTikum"></strong></li>
              <li><i data-lucide="calendar"></i>Joined <span id="pJoined"></span></li>
            </ul>
          </div>
          <div class="profile-card__actions">
            <a href="settings.html#profile" class="btn btn--primary btn--keep" id="editProfileBtn"><i data-lucide="pencil"></i><span>Edit Profile</span></a>
            <a href="#" class="btn btn--primary btn--keep" id="chatUserBtn" hidden><i data-lucide="message-circle-more"></i><span>Chat</span></a>
            <button type="button" class="btn btn--outline" id="shareProfile"><i data-lucide="share-2"></i><span>Share</span></button>
          </div>
        </div>
        <dl class="profile-stats">
          <div><dt>Live listings</dt><dd id="statListings">0</dd></div>
          <div><dt>Items sold</dt><dd id="statSold">0</dd></div>
          <div><dt>Requests posted</dt><dd id="statRequests">0</dd></div>
          <div><dt>Member since</dt><dd id="statJoined">–</dd></div>
        </dl>
      </section>

      <!-- Tabs -->
      <div class="status-tabs" role="tablist" aria-label="Profile sections">
        <button type="button" class="status-tab is-active" role="tab" aria-selected="true" aria-controls="tabListings" data-tab="listings">
          <i data-lucide="shopping-bag"></i><span>Listings</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" aria-controls="tabAbout" data-tab="about">
          <i data-lucide="info"></i><span>About</span>
        </button>
      </div>

      <section id="tabListings" role="tabpanel" class="tab-panel">
        <div class="mini-grid" id="listingGrid"></div>
        <a href="my-listings.html" class="btn btn--outline btn--block manage-btn" id="manageBtn"><span>Manage My Listings</span> <i data-lucide="arrow-right"></i></a>
      </section>

      <section id="tabAbout" role="tabpanel" class="tab-panel panel" hidden>
        <h2 class="panel__title">About</h2>
        <ul class="about-list">
          <li><span>Faculty</span><strong id="aFaculty"></strong></li>
          <li><span>Usual meet-up point</span><strong id="aTikum"></strong></li>
                      <li><span>WhatsApp</span><strong id="aPhone"></strong></li>
          <li><span>Member since</span><strong id="aJoined"></strong></li>
        </ul>
      </section>
    </main>

    <!-- ---------- RIGHT PANEL ---------- -->
    <aside class="rightbar" aria-label="Profile tools">
      <section class="panel" id="strengthPanel">
        <div class="panel__row">
          <h2 class="panel__title"><i data-lucide="user-check" class="icon-green"></i> Profile Strength</h2>
          <span class="strength-text" id="strengthText">0%</span>
        </div>
        <div class="progress" aria-hidden="true"><div class="progress__bar" id="strengthBar"></div></div>
        <ul class="checklist-static" id="strengthList"></ul>
        <a href="settings.html#profile" class="btn btn--outline btn--block"><span>Complete in Settings</span> <i data-lucide="arrow-right"></i></a>
      </section>

      <section class="panel">
        <h2 class="panel__title"><i data-lucide="shield-check" class="icon-green"></i> Trade Safely</h2>
        <ul class="tip-list">
          <li><i data-lucide="circle-check"></i> Meet at your faculty or another public tikum.</li>
          <li><i data-lucide="circle-check"></i> Check transfers in your own banking app.</li>
          <li><i data-lucide="circle-check"></i> Keep chats inside GaMarketplace.</li>
        </ul>
        <a href="scam-watch.html" class="btn btn--outline btn--block"><span>Open Scam Watch</span> <i data-lucide="arrow-right"></i></a>
      </section>
    </aside>
  </div>
@endsection
