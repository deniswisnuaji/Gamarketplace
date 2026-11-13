{{-- Converted from frontend-original/admin-login.html. --}}

@extends('layouts.marketplace-auth')

@section('title', 'Admin Login — GaMarketplace')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/admin-login.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/admin-login.js') }}" defer></script>
@endpush

@section('content')
  <main class="admin-auth">
    <p class="admin-auth__brand">GaMarketplace <span class="admin-badge"><i data-lucide="shield"></i> Admin</span></p>

    <section class="admin-card" aria-labelledby="adminTitle">
      <span class="admin-card__icon" aria-hidden="true"><i data-lucide="shield-check"></i></span>
      <h1 id="adminTitle">Admin sign in</h1>
      <p class="admin-card__sub">For moderators only. Review listings, scam reports and users.</p>

      <div class="demo-box">
        <i data-lucide="info"></i>
        <div>
          <strong>Default admin account</strong> (created on first run — change it in <code>js/store.js</code>)<br />
          Email <code>admin@ugm.ac.id</code> · Password <code>Admin2026</code><br />
          <button type="button" id="fillDemo">Fill in default account</button>
        </div>
      </div>

      <form id="adminForm" novalidate>
        <label class="auth-label" for="adminEmail">Admin email</label>
        <div class="input-wrap">
          <i data-lucide="mail"></i>
          <input type="email" id="adminEmail" autocomplete="username" placeholder="admin@ugm.ac.id" />
        </div>
        <p class="field-error" data-error="adminEmail" hidden></p>

        <label class="auth-label" for="adminPassword">Password</label>
        <div class="input-wrap">
          <i data-lucide="lock"></i>
          <input type="password" id="adminPassword" autocomplete="current-password" placeholder="Your password" />
          <button type="button" class="eye-btn" id="eyeBtn" aria-label="Show password"><i data-lucide="eye"></i></button>
        </div>
        <p class="caps-warn" id="capsWarn" hidden><i data-lucide="triangle-alert"></i> Caps Lock is on</p>
        <p class="field-error" data-error="adminPassword" hidden></p>

        <label class="check-row"><input type="checkbox" id="adminRemember" /> Keep me signed in (only on your own device)</label>

        <p class="form-alert" id="adminAlert" role="alert" hidden></p>
        <button type="submit" class="btn btn--primary auth-submit" id="adminSubmit"><i data-lucide="log-in"></i><span>Sign in to Admin Panel</span></button>
      </form>
    </section>

    <p class="admin-foot">Not an admin? <a href="login.html">Go to student login</a></p>
  </main>
@endsection
