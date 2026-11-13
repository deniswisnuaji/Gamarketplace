{{-- Converted from frontend-original/wishlist.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Wishlist — GaMarketplace')

@section('description', 'Items, auctions and requests you saved.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/wishlist.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/wishlist.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT ---------- -->
  <div class="layout layout--wide">
    <main class="main" id="main">
      <header class="page-head">
        <div>
          <h1><span class="title-icon title-icon--red"><i data-lucide="heart"></i></span> Wishlist</h1>
          <p>Items, auctions and requests you saved. We'll tell you when prices drop.</p>
        </div>
        <div class="page-head__actions">
          <button type="button" class="btn btn--outline" id="clearUnavailable"><i data-lucide="trash-2"></i><span>Remove unavailable</span></button>
        </div>
      </header>

      <div class="bid-toolbar wl-toolbar">
        <label class="field field--search">
          <i data-lucide="search"></i>
          <span class="sr-only">Search wishlist</span>
          <input type="search" id="wlSearch" placeholder="Search your wishlist" autocomplete="off" />
        </label>
        <label class="field field--sort">
          <span class="field__label">Sort by</span>
          <select id="sortSelect">
            <option value="recent">Recently saved</option>
            <option value="price-asc">Price (low–high)</option>
            <option value="price-desc">Price (high–low)</option>
            <option value="drop">Biggest price drop</option>
          </select>
          <i data-lucide="chevrons-up-down" class="field__chevron"></i>
        </label>
      </div>

      <div class="status-tabs" role="tablist" aria-label="Saved type">
        <button type="button" class="status-tab is-active" role="tab" aria-selected="true" data-tab="all"><span>All (<span data-count="all">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="item"><i data-lucide="shopping-bag"></i><span>Items (<span data-count="item">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="auction"><i data-lucide="gavel"></i><span>Auctions (<span data-count="auction">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="request"><i data-lucide="search"></i><span>Requests (<span data-count="request">0</span>)</span></button>
      </div>

      <div class="wl-grid" id="wlGrid"></div>

      <div class="empty-state" id="emptyState" hidden>
        <p><strong id="emptyTitle">Your wishlist is empty.</strong></p>
        <p>Tap the ♡ on anything you like and it will show up here.</p>
        <a href="marketplace.html" class="btn btn--primary btn--keep"><i data-lucide="shopping-bag"></i><span>Browse Marketplace</span></a>
      </div>
    </main>
  </div>
@endsection
