{{-- Converted from frontend-original/login.html. --}}

@extends('layouts.marketplace-auth')

@section('title', 'Log in — GaMarketplace')

@section('description', 'Log in to GaMarketplace, the marketplace for UGM students.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/login.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/login.js') }}" defer></script>
@endpush

@section('content')
  <div class="auth">
    <!-- ================= BRAND SIDE ================= -->
    <aside class="auth-brand" aria-hidden="true">
      <a href="main.html" class="auth-logo" tabindex="-1">GaMarketplace.</a>
      <div>
        <h1>Your campus, <span>your marketplace.</span></h1>
        <p class="auth-brand__lead">Buy, bid and find what you need from fellow UGM students — closer, cheaper, and safer.</p>
        <ul class="auth-perks">
          <li><span><i data-lucide="shopping-bag"></i></span>Secondhand items around campus</li>
          <li><span><i data-lucide="gavel"></i></span>Campus auctions between students</li>
          <li><span><i data-lucide="shield-check"></i></span>Every listing checked by an admin</li>
        </ul>
      </div>
      <p class="auth-brand__foot">Exclusively for UGM students</p>
    </aside>

    <!-- ================= FORM SIDE ================= -->
    <main class="auth-main">
      <div class="auth-card">
        <a href="main.html" class="auth-card__logo">GaMarketplace.</a>

        <div class="auth-tabs" role="tablist" aria-label="Log in or create an account">
          <button type="button" role="tab" id="tabLogin" aria-selected="true" aria-controls="loginForm">Log in</button>
          <button type="button" role="tab" id="tabRegister" aria-selected="false" aria-controls="registerForm">Create account</button>
        </div>

        <h2 id="authTitle">Welcome back 👋</h2>
        <p class="auth-card__sub" id="authSub">Log in to buy, sell, and chat with UGM students.</p>

        <!-- UGM account sign-in. For now it signs in as user@mail.ugm.ac.id (see js/store.js). -->
        <button type="button" class="sso-btn" id="ssoBtn">
          
          <span>Continue with UGM account</span>
        </button>
        <p class="sso-note">For now this signs you in as <strong>user@mail.ugm.ac.id</strong>.</p>
        <div class="divider">or use your UGM email</div>

        <!-- LOG IN -->
        <form class="auth-form" id="loginForm" role="tabpanel" aria-labelledby="tabLogin" novalidate>
          <label class="auth-label" for="loginEmail">UGM email</label>
          <div class="input-wrap">
            <i data-lucide="mail"></i>
            <input type="email" id="loginEmail" autocomplete="email" placeholder="nama@mail.ugm.ac.id" />
          </div>
          <p class="field-error" data-error="loginEmail" hidden></p>

          <label class="auth-label" for="loginPassword">Password <button type="button" class="link-btn" id="forgotBtn">Forgot password?</button></label>
          <div class="input-wrap">
            <i data-lucide="lock"></i>
            <input type="password" id="loginPassword" autocomplete="current-password" placeholder="Your password" />
            <button type="button" class="eye-btn" data-eye="loginPassword" aria-label="Show password"><i data-lucide="eye"></i></button>
          </div>
          <p class="caps-warn" data-caps="loginPassword" hidden><i data-lucide="triangle-alert"></i> Caps Lock is on</p>
          <p class="field-error" data-error="loginPassword" hidden></p>

          <label class="check-row"><input type="checkbox" id="remember" checked /> Keep me logged in on this device</label>

          <p class="form-alert" id="loginAlert" role="alert" hidden></p>
          <button type="submit" class="btn btn--primary auth-submit" id="loginSubmit"><i data-lucide="log-in"></i><span>Log in</span></button>
          <p class="auth-switch">New to GaMarketplace? <button type="button" class="link-btn" data-go="register">Create an account</button></p>
        </form>

        <!-- REGISTER -->
        <form class="auth-form" id="registerForm" role="tabpanel" aria-labelledby="tabRegister" novalidate hidden>
          <label class="auth-label" for="regName">Full name</label>
          <div class="input-wrap">
            <i data-lucide="user-round"></i>
            <input type="text" id="regName" autocomplete="name" maxlength="50" placeholder="e.g. Shafa Sekar Arum" />
          </div>
          <p class="field-error" data-error="regName" hidden></p>

          <label class="auth-label" for="regEmail">UGM email</label>
          <div class="input-wrap">
            <i data-lucide="mail"></i>
            <input type="email" id="regEmail" autocomplete="email" placeholder="nama@mail.ugm.ac.id" />
          </div>
          <p class="field-hint">Only @mail.ugm.ac.id or @ugm.ac.id emails can join.</p>
          <p class="field-error" data-error="regEmail" hidden></p>

          <label class="auth-label" for="regFaculty">Faculty</label>
          <div class="input-wrap">
            <i data-lucide="graduation-cap"></i>
            <select id="regFaculty"><option value="" selected disabled>Choose your faculty</option><option value="Biologi">Biologi</option><option value="FEB">FEB</option><option value="Farmasi">Farmasi</option><option value="Filsafat">Filsafat</option><option value="Geografi">Geografi</option><option value="FH">FH</option><option value="FIB">FIB</option><option value="FISIPOL">FISIPOL</option><option value="FK-KMK">FK-KMK</option><option value="FKG">FKG</option><option value="FKH">FKH</option><option value="FKT">FKT</option><option value="FMIPA">FMIPA</option><option value="FAPERTA">FAPERTA</option><option value="FAPET">FAPET</option><option value="Psikologi">Psikologi</option><option value="FT">FT</option><option value="FTP">FTP</option><option value="SV">SV</option><option value="SPs">SPs</option></select>
          </div>
          <p class="field-error" data-error="regFaculty" hidden></p>

          <label class="auth-label" for="regPassword">Password</label>
          <div class="input-wrap">
            <i data-lucide="lock"></i>
            <input type="password" id="regPassword" autocomplete="new-password" placeholder="At least 8 characters" />
            <button type="button" class="eye-btn" data-eye="regPassword" aria-label="Show password"><i data-lucide="eye"></i></button>
          </div>
          <div class="strength" id="strength" data-level="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
          <p class="field-hint" id="strengthText" aria-live="polite">Use 8+ characters with letters and numbers.</p>
          <p class="caps-warn" data-caps="regPassword" hidden><i data-lucide="triangle-alert"></i> Caps Lock is on</p>
          <p class="field-error" data-error="regPassword" hidden></p>

          <label class="auth-label" for="regConfirm">Confirm password</label>
          <div class="input-wrap">
            <i data-lucide="lock-keyhole"></i>
            <input type="password" id="regConfirm" autocomplete="new-password" placeholder="Type it again" />
          </div>
          <p class="field-error" data-error="regConfirm" hidden></p>

          <label class="check-row"><input type="checkbox" id="regAgree" /> <span>I agree to trade honestly and follow the <a href="scam-watch.html" target="_blank">safety rules</a>.</span></label>
          <p class="field-error" data-error="regAgree" hidden></p>

          <p class="form-alert" id="registerAlert" role="alert" hidden></p>
          <button type="submit" class="btn btn--primary auth-submit" id="registerSubmit"><i data-lucide="user-plus"></i><span>Create account</span></button>
          <p class="auth-switch">Already have an account? <button type="button" class="link-btn" data-go="login">Log in</button></p>
        </form>

        <p class="auth-switch"><a href="admin-login.html" class="link-btn">Admin or moderator? Sign in here</a></p>
        <p class="auth-legal">Accounts are saved in this browser for now. In the Laravel app this form posts to the backend's auth routes with @@csrf.</p>
      </div>
    </main>
  </div>

  <!-- Forgot password -->
  <dialog class="fp-dialog" id="fpDialog" aria-labelledby="fpTitle">
    <form method="dialog" id="fpForm" novalidate>
      <h2 id="fpTitle">Reset your password</h2>
      <p>Enter your UGM email and we'll send you a reset link.</p>
      <label class="auth-label" for="fpEmail">UGM email</label>
      <div class="input-wrap">
        <i data-lucide="mail"></i>
        <input type="email" id="fpEmail" autocomplete="email" placeholder="nama@mail.ugm.ac.id" />
      </div>
      <p class="field-error" data-error="fpEmail" hidden></p>
      <p class="form-alert form-alert--ok" id="fpDone" hidden><i data-lucide="mail-check"></i><span>If that email has an account, a reset link is on its way. Check your inbox.</span></p>
      <div class="fp-actions">
        <button type="button" class="btn btn--outline" id="fpCancel"><span>Close</span></button>
        <button type="submit" class="btn btn--primary" id="fpSubmit"><span>Send link</span></button>
      </div>
    </form>
  </dialog>
@endsection
