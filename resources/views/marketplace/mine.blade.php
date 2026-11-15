{{-- Converted from frontend-original/my-listings.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'My Listings — GaMarketplace')

@section('description', 'Manage your GaMarketplace listings.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/my-listings.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/my-listings.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT ---------- -->
  <div class="layout layout--wide">
    <main class="main" id="main">
      <header class="page-head">
        <div>
          <h1><span class="title-icon"><i data-lucide="package"></i></span> My Listings</h1>
          <p>Manage your items. New listings are checked by an admin before they go public.</p>
        </div>
        <div class="page-head__actions">
          <a href="list-item.html" class="btn btn--primary btn--keep"><i data-lucide="plus"></i><span>List an Item</span></a>
        </div>
      </header>

      <section class="sw-stats" aria-label="Listing summary">
        <div class="sw-stat sw-stat--green"><i data-lucide="circle-check"></i><div><strong id="stActive">0</strong><span>Live listings</span></div></div>
        <div class="sw-stat sw-stat--yellow"><i data-lucide="hourglass"></i><div><strong id="stPending">0</strong><span>Waiting for review</span></div></div>
        <div class="sw-stat sw-stat--blue"><i data-lucide="handshake"></i><div><strong id="stSold">0</strong><span>Sold</span></div></div>
        <div class="sw-stat sw-stat--purple"><i data-lucide="eye"></i><div><strong id="stViews">0</strong><span>Total views</span></div></div>
      </section>

      <div class="bid-toolbar">
        <label class="field field--search">
          <i data-lucide="search"></i>
          <span class="sr-only">Search my listings</span>
          <input type="search" id="mlSearch" placeholder="Search my listings" autocomplete="off" />
        </label>
        <label class="field field--select">
          <i data-lucide="layers"></i>
          <span class="sr-only">Listing type</span>
          <select id="typeSelect">
            <option value="">All Types</option>
            <option value="marketplace">Marketplace</option>
            <option value="auction">Auction</option>
            
          </select>
          <i data-lucide="chevron-down" class="field__chevron"></i>
        </label>
        <label class="field field--sort">
          <span class="field__label">Sort by</span>
          <select id="sortSelect">
            <option value="newest">Newest</option>
            <option value="views">Most Views</option>
            <option value="price">Highest Price</option>
          </select>
          <i data-lucide="chevrons-up-down" class="field__chevron"></i>
        </label>
      </div>

      <div class="status-tabs" role="tablist" aria-label="Listing status">
        <button type="button" class="status-tab is-active" role="tab" aria-selected="true" data-tab="all"><span>All (<span data-count="all">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="live"><i data-lucide="circle-check"></i><span>Live (<span data-count="live">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="pending"><i data-lucide="hourglass"></i><span>In Review (<span data-count="pending">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="sold"><i data-lucide="handshake"></i><span>Sold (<span data-count="sold">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="rejected"><i data-lucide="circle-x"></i><span>Rejected (<span data-count="rejected">0</span>)</span></button>
      </div>

      <ul class="ml-list" id="mlList"></ul>

      <div class="empty-state" id="emptyState" hidden>
        <p><strong>No listings here.</strong></p>
        <p>Try another tab, or list something you no longer use.</p>
        <a href="list-item.html" class="btn btn--primary btn--keep"><i data-lucide="plus"></i><span>List an Item</span></a>
      </div>
    </main>
  </div>

<!-- Edit dialog -->
<dialog class="bid-dialog form-dialog" id="editDialog" aria-labelledby="editTitle">
  <form method="dialog" class="form-dialog__inner" id="editForm" novalidate>
    <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close"><i data-lucide="x"></i></button>
    <h2 id="editTitle">Edit listing</h2>
    <p class="form-dialog__sub" id="editNote">Changes to title, photos or description are reviewed again by an admin.</p>

    <label class="bx-label" for="eTitle">Title</label>
    <input type="text" id="eTitle" class="text-input" maxlength="70" />

    <div class="two-col">
      <div>
        <label class="bx-label" for="ePrice" id="ePriceLabel">Price</label>
        <div class="prefix-input"><span>Rp</span><input type="text" id="ePrice" inputmode="numeric" autocomplete="off" /></div>
      </div>
      <div>
        <label class="bx-label" for="eCondition">Condition</label>
        <select id="eCondition" class="select">
          <option value="like-new">Like New</option>
          <option value="good">Good</option>
          <option value="fair">Fair</option>
        </select>
      </div>
    </div>

    <label class="bx-label" for="eTikum">Suggested meet-up point (tikum)</label>
    <select id="eTikum" class="select"><option value="Biologi">Biologi</option><option value="FEB">FEB</option><option value="Farmasi">Farmasi</option><option value="Filsafat">Filsafat</option><option value="Geografi">Geografi</option><option value="FH">FH</option><option value="FIB">FIB</option><option value="FISIPOL">FISIPOL</option><option value="FK-KMK">FK-KMK</option><option value="FKG">FKG</option><option value="FKH">FKH</option><option value="FKT">FKT</option><option value="FMIPA">FMIPA</option><option value="FAPERTA">FAPERTA</option><option value="FAPET">FAPET</option><option value="Psikologi">Psikologi</option><option value="FT">FT</option><option value="FTP">FTP</option><option value="SV">SV</option><option value="SPs">SPs</option></select>

    <label class="bx-label" for="eDesc">Description</label>
    <textarea id="eDesc" class="text-input" rows="3" maxlength="500"></textarea>

    <p class="form-error" id="eError" role="alert" hidden></p>
    <div class="bid-dialog__actions">
      <button type="button" class="btn btn--outline" data-close>Cancel</button>
      <button type="submit" class="btn btn--primary"><span>Save changes</span></button>
    </div>
  </form>
</dialog>
@endsection
