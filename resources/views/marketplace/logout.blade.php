{{-- Converted from frontend-original/logout.html. --}}

@extends('layouts.marketplace-auth')

@section('title', 'Log out — GaMarketplace')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/logout.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/logout.js') }}" defer></script>
@endpush

@section('content')
  <main>
    <!-- Step 1: confirm -->
    <section class="logout-card" id="confirmCard" aria-labelledby="confirmTitle">
      <a href="main.html" class="logout-brand"><img src="{{ asset('marketplace/img/logo.png') }}" alt="" onerror="this.remove()" />GaMarketplace.</a>
      <span class="logout-icon" aria-hidden="true"><i data-lucide="log-out"></i></span>
      <h1 id="confirmTitle">Log out of GaMarketplace?</h1>
      <p>You can log back in anytime with your UGM account.</p>
      <label class="check-row"><input type="checkbox" id="clearData" /> Also clear drafts and layout settings on this device</label>
      <div class="logout-actions">
        <button type="button" class="btn btn--primary" id="logoutBtn"><i data-lucide="log-out"></i><span>Log out</span></button>
        <button type="button" class="btn btn--outline" id="cancelBtn"><span>Cancel</span></button>
      </div>
    </section>

    <!-- Step 2: done -->
    <section class="logout-card" id="doneCard" aria-labelledby="doneTitle" hidden>
      <a href="main.html" class="logout-brand"><img src="{{ asset('marketplace/img/logo.png') }}" alt="" onerror="this.remove()" />GaMarketplace.</a>
      <span class="logout-icon logout-icon--done" aria-hidden="true"><i data-lucide="circle-check"></i></span>
      <h1 id="doneTitle" tabindex="-1">You're logged out</h1>
      <p>See you again soon. Stay safe when trading on campus!</p>
      <div class="logout-actions">
        <a href="login.html" class="btn btn--primary"><i data-lucide="log-in"></i><span>Log in again</span></a>
      </div>
      <p class="logout-note">On a shared computer, also close the browser.</p>
    </section>
  </main>
@endsection
