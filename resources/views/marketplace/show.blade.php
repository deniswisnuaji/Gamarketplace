{{-- Converted from frontend-original/item.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Item — GaMarketplace')

@section('description', 'Item details on GaMarketplace.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/item.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/item.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT ---------- -->
  <div class="layout">
    <main class="main" id="main">
      <a href="marketplace.html" class="back-link" id="backLink"><i data-lucide="arrow-left"></i> Back</a>

      <div id="itemRoot" aria-live="polite">
        <p class="hint">Loading…</p>
      </div>
    </main>

    <aside class="rightbar" aria-label="Seller and safety">
      <section class="panel" id="sellerPanel" hidden></section>
      <section class="panel">
        <h2 class="panel__title"><i data-lucide="shield-check" class="icon-green"></i> Before you meet</h2>
        <ul class="tip-list">
          <li><i data-lucide="circle-check"></i> Chat inside GaMarketplace and agree on a public tikum.</li>
          <li><i data-lucide="circle-check"></i> Check the item before you pay.</li>
          <li><i data-lucide="circle-check"></i> Confirm transfers in your own banking app.</li>
        </ul>
        <a href="meetup-map.html" class="btn btn--outline btn--block"><span>See meet-up spots</span> <i data-lucide="arrow-right"></i></a>
      </section>
    </aside>
  </div>
@endsection
