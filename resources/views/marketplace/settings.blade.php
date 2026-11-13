{{-- Converted from frontend-original/settings.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Settings — GaMarketplace')

@section('description', 'Manage your GaMarketplace profile, notifications and privacy.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/settings.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/settings.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT ---------- -->
  <div class="settings-layout">
    <!-- Section navigation -->
    <nav class="settings-nav" aria-label="Settings sections">
      <h1 class="settings-nav__title">Settings</h1>
      <a href="#profile" class="settings-nav__link is-active"><i data-lucide="user-round"></i>Profile</a>
      <a href="#notifications" class="settings-nav__link"><i data-lucide="bell"></i>Notifications</a>
      <a href="#privacy" class="settings-nav__link"><i data-lucide="shield-check"></i>Privacy &amp; Safety</a>
      <a href="#account" class="settings-nav__link"><i data-lucide="key-round"></i>Account</a>
      <a href="#danger" class="settings-nav__link settings-nav__link--danger"><i data-lucide="triangle-alert"></i>Danger Zone</a>
    </nav>

    <form class="settings-main" id="settingsForm" novalidate>
      <!-- PROFILE -->
      <section class="panel settings-card" id="profile">
        <h2 class="settings-card__title">Profile</h2>
        <p class="settings-card__sub">This is what other students see on your listings and profile.</p>

        <div class="photo-row">
          <div class="avatar-lg photo-row__avatar" id="photoPreview" aria-hidden="true"></div>
          <div>
            <div class="photo-row__actions">
              <label class="btn btn--outline btn--sm" for="photoInput"><i data-lucide="upload"></i>Upload photo</label>
              <input type="file" id="photoInput" class="sr-only" accept="image/jpeg,image/png,image/webp" />
              <button type="button" class="btn btn--outline btn--sm" id="photoRemove"><i data-lucide="trash-2"></i>Remove</button>
            </div>
            <p class="hint">JPG, PNG or WEBP, max 5 MB. A clear face photo builds trust.</p>
          </div>
        </div>

        <div class="two-col">
          <div>
            <label class="bx-label" for="sName">Display name</label>
            <input type="text" id="sName" class="text-input" maxlength="40" autocomplete="name" />
          </div>
          <div>
            <label class="bx-label" for="sUsername">Username</label>
            <div class="prefix-input"><span>@</span><input type="text" id="sUsername" maxlength="24" autocomplete="username" /></div>
          </div>
        </div>

        <div class="two-col">
          <div>
            <label class="bx-label" for="sFaculty">Faculty</label>
            <select id="sFaculty" class="select">
              <option value="" disabled>Choose your faculty</option>
                <option value="Biologi">Biologi</option>
                <option value="FEB">FEB</option>
                <option value="Farmasi">Farmasi</option>
                <option value="Filsafat">Filsafat</option>
                <option value="Geografi">Geografi</option>
                <option value="FH">FH</option>
                <option value="FIB">FIB</option>
                <option value="FISIPOL">FISIPOL</option>
                <option value="FK-KMK">FK-KMK</option>
                <option value="FKG">FKG</option>
                <option value="FKH">FKH</option>
                <option value="FKT">FKT</option>
                <option value="FMIPA">FMIPA</option>
                <option value="FAPERTA">FAPERTA</option>
                <option value="FAPET">FAPET</option>
                <option value="Psikologi">Psikologi</option>
                <option value="FT">FT</option>
                <option value="FTP">FTP</option>
                <option value="SV">SV</option>
                <option value="SPs">SPs</option>
            </select>
          </div>
          <div>
            <label class="bx-label" for="sTikum">Default meet-up point (tikum)</label>
            <select id="sTikum" class="select">
              <option value="" disabled>Choose a meet-up point</option>
                <option value="Biologi">Biologi</option>
                <option value="FEB">FEB</option>
                <option value="Farmasi">Farmasi</option>
                <option value="Filsafat">Filsafat</option>
                <option value="Geografi">Geografi</option>
                <option value="FH">FH</option>
                <option value="FIB">FIB</option>
                <option value="FISIPOL">FISIPOL</option>
                <option value="FK-KMK">FK-KMK</option>
                <option value="FKG">FKG</option>
                <option value="FKH">FKH</option>
                <option value="FKT">FKT</option>
                <option value="FMIPA">FMIPA</option>
                <option value="FAPERTA">FAPERTA</option>
                <option value="FAPET">FAPET</option>
                <option value="Psikologi">Psikologi</option>
                <option value="FT">FT</option>
                <option value="FTP">FTP</option>
                <option value="SV">SV</option>
                <option value="SPs">SPs</option>
            </select>
          </div>
        </div>

        <label class="bx-label" for="sBio">Bio <small>(optional)</small></label>
        <textarea id="sBio" class="text-input" rows="3" maxlength="160"></textarea>
        <p class="hint"><span id="bioCount">0</span>/160</p>

        <label class="bx-label" for="sPhone">WhatsApp number <small>(optional)</small></label>
        <input type="tel" id="sPhone" class="text-input" maxlength="16" placeholder="08xx-xxxx-xxxx" autocomplete="tel" />
        <p class="hint">Only shown to buyers if you turn it on in Privacy &amp; Safety.</p>
      </section>

      <!-- NOTIFICATIONS -->
      <section class="panel settings-card" id="notifications">
        <h2 class="settings-card__title">Notifications</h2>
        <p class="settings-card__sub">Choose what you want to hear about.</p>
        <ul class="toggle-list">
          <li><div><strong>New messages</strong><span>When a buyer or seller chats you.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifMessages" aria-label="New messages" /><span class="switch__track"></span></label></li>
          <li><div><strong>Offers on my listings &amp; requests</strong><span>Bids on your auctions and “I Have This” offers.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifOffers" aria-label="Offers" /><span class="switch__track"></span></label></li>
          <li><div><strong>Outbid alerts</strong><span>When someone bids higher than you.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifOutbid" aria-label="Outbid alerts" /><span class="switch__track"></span></label></li>
          <li><div><strong>Price drops on my wishlist</strong><span>When a saved item gets cheaper.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifPriceDrop" aria-label="Price drops" /><span class="switch__track"></span></label></li>
          <li><div><strong>Scam alerts</strong><span>Verified high-risk alerts from Scam Watch.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifScam" aria-label="Scam alerts" /><span class="switch__track"></span></label></li>
          <li><div><strong>Email summary</strong><span>A weekly email to your UGM address.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifEmail" aria-label="Email summary" /><span class="switch__track"></span></label></li>
        </ul>
      </section>

      <!-- PRIVACY -->
      <section class="panel settings-card" id="privacy">
        <h2 class="settings-card__title">Privacy &amp; Safety</h2>
        <p class="settings-card__sub">Control what other students can see.</p>
        <ul class="toggle-list">
          <li><div><strong>Show my WhatsApp number to buyers</strong><span>Off = buyers can only use in-app chat (safer).</span></div>
            <label class="switch"><input type="checkbox" data-pref="showPhone" aria-label="Show WhatsApp number" /><span class="switch__track"></span></label></li>
          <li><div><strong>Show my faculty on my profile</strong><span>Helps buyers pick a nearby meet-up point.</span></div>
            <label class="switch"><input type="checkbox" data-pref="showFaculty" aria-label="Show faculty" /><span class="switch__track"></span></label></li>
          <li><div><strong>Only chat with verified UGM accounts</strong><span>Block messages from unverified accounts.</span></div>
            <label class="switch"><input type="checkbox" data-pref="verifiedOnly" aria-label="Verified accounts only" /><span class="switch__track"></span></label></li>
        </ul>
        <a href="scam-watch.html" class="inline-link"><i data-lucide="shield-alert"></i> Read safety tips on Scam Watch</a>
      </section>

      <!-- ACCOUNT -->
      <section class="panel settings-card" id="account">
        <h2 class="settings-card__title">Account</h2>
        <p class="settings-card__sub">You sign in with your UGM email and password.</p>
        <label class="bx-label" for="sEmail">UGM email</label>
        <input type="email" id="sEmail" class="text-input" value="" readonly />
        <p class="hint">Your email can't be changed.</p>

        <h3 class="settings-sub">Change password</h3>
        <div class="pw-form" id="pwForm">
          <div class="two-col">
            <div><label class="bx-label" for="pwOld">Current password</label><input type="password" id="pwOld" class="text-input" autocomplete="current-password" /></div>
            <div><label class="bx-label" for="pwNew">New password</label><input type="password" id="pwNew" class="text-input" autocomplete="new-password" placeholder="8+ characters, letters and numbers" /></div>
          </div>
          <p class="form-error" id="pwError" role="alert" hidden></p>
          <button type="button" class="btn btn--outline btn--sm" id="pwSave"><i data-lucide="key-round"></i>Update password</button>
        </div>
        <div class="account-row">
          <div><strong>Signed in on this device</strong><span id="sinceText">Logged in</span></div>
          <a href="logout.html" class="btn btn--outline btn--sm"><i data-lucide="log-out"></i>Log out</a>
        </div>
      </section>

      <!-- DANGER -->
      <section class="panel settings-card settings-card--danger" id="danger">
        <h2 class="settings-card__title">Danger Zone</h2>
        <div class="account-row">
          <div><strong>Delete my account</strong><span>Permanently removes your profile, listings and chats.</span></div>
          <button type="button" class="btn btn--danger btn--sm" id="deleteBtn">Delete account</button>
        </div>
      </section>

      <!-- Sticky save bar (shows when something changed) -->
      <div class="save-bar" id="saveBar" hidden>
        <span><i data-lucide="circle-alert"></i> You have unsaved changes</span>
        <div>
          <button type="button" class="btn btn--outline btn--sm" id="discardBtn">Discard</button>
          <button type="submit" class="btn btn--primary btn--sm btn--keep"><span>Save changes</span></button>
        </div>
      </div>
    </form>
  </div>

<!-- Delete account dialog -->
<dialog class="bid-dialog form-dialog" id="deleteDialog" aria-labelledby="delTitle">
  <form method="dialog" class="form-dialog__inner" id="deleteForm" novalidate>
    <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close"><i data-lucide="x"></i></button>
    <h2 id="delTitle">Delete your account?</h2>
    <p class="form-dialog__sub">This can't be undone. Your listings, offers, and chats will be removed. Type <strong>DELETE</strong> to confirm.</p>
    <input type="text" id="deleteConfirm" class="text-input" autocomplete="off" aria-label="Type DELETE to confirm" />
    <div class="bid-dialog__actions">
      <button type="button" class="btn btn--outline" data-close>Cancel</button>
      <button type="submit" class="btn btn--danger" id="deleteSubmit" disabled><span>Delete forever</span></button>
    </div>
  </form>
</dialog>
@endsection
