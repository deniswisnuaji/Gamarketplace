{{-- Converted from frontend-original/meetup-map.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Meet-Up Map — GaMarketplace')

@section('description', 'Suggested public meet-up points (tikum) around UGM.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/meetup-map.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/meetup-map.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT GRID ---------- -->
  <div class="layout">
    <main class="main" id="main">
      <header class="page-head">
        <div>
          <h1><span class="title-icon"><i data-lucide="map"></i></span> Campus Meet-Up Map</h1>
          <p>Suggested public meet-up points (tikum) around UGM. Pick one before you COD.</p>
        </div>
      </header>

      <section class="map-card" aria-label="Schematic campus map">
        <div class="map" id="map">
          <svg class="map__bg" viewBox="0 0 1000 620" preserveAspectRatio="none" aria-hidden="true">
            <rect width="1000" height="620" fill="var(--map-ground)" />
            <path d="M60 80 Q180 40 300 90 L280 210 Q160 230 70 190 Z" fill="var(--map-green)" />
            <path d="M700 420 Q820 380 940 430 L930 560 Q800 590 690 540 Z" fill="var(--map-green)" />
            <path d="M380 470 Q470 440 560 480 L550 570 Q460 590 370 560 Z" fill="var(--map-green)" />
            <path d="M760 70 Q860 50 950 100 L930 200 Q840 220 750 180 Z" fill="var(--map-green)" />
            <g stroke="var(--map-road)" stroke-linecap="round" fill="none">
              <path d="M0 330 H1000" stroke-width="22" />
              <path d="M500 0 V620" stroke-width="26" />
              <path d="M0 140 Q300 160 500 140 T1000 150" stroke-width="12" />
              <path d="M0 500 Q250 480 500 510 T1000 500" stroke-width="12" />
              <path d="M250 0 V620" stroke-width="10" />
              <path d="M760 0 V620" stroke-width="10" />
            </g>
            <path d="M500 0 V620" stroke="var(--map-boulevard)" stroke-width="6" stroke-dasharray="2 14" stroke-linecap="round" />
            <text x="512" y="610" class="map__road-label">UGM Boulevard</text>
          </svg>
          <div class="map__pins" id="mapPins"></div>
          <p class="map__note"><i data-lucide="info"></i> Schematic map, not to scale. Use “Directions” for the exact location.</p>
        </div>
      </section>

      <!-- Selected spot -->
      <section class="panel spot-detail" id="spotDetail" aria-live="polite">
        <p class="spot-detail__empty">Select a pin on the map or a spot in the list.</p>
      </section>
    </main>

    <!-- ---------- RIGHT PANEL ---------- -->
    <aside class="rightbar" aria-label="Meet-up points">
      <section class="panel spot-panel">
        <label class="field field--search">
          <i data-lucide="search"></i>
          <span class="sr-only">Search meet-up points</span>
          <input type="search" id="spotSearch" placeholder="Search FT, FEB, Balairung…" autocomplete="off" />
        </label>
        <div class="chip-row" role="group" aria-label="Filter spots">
          <button type="button" class="chip is-active" data-kind="all" aria-pressed="true">All (<span data-count="all">0</span>)</button>
          <button type="button" class="chip" data-kind="faculty" aria-pressed="false">Faculties (<span data-count="faculty">0</span>)</button>
          <button type="button" class="chip" data-kind="public" aria-pressed="false">Public spots (<span data-count="public">0</span>)</button>
        </div>
        <ul class="spot-list" id="spotList"></ul>
      </section>

      <section class="panel">
        <h2 class="panel__title"><i data-lucide="shield-check" class="icon-green"></i> Safe Meet-Up Tips</h2>
        <ul class="tip-list">
          <li><i data-lucide="circle-check"></i> Meet in daylight in a busy, open area.</li>
          <li><i data-lucide="circle-check"></i> Tell a friend where and when you're meeting.</li>
          <li><i data-lucide="circle-check"></i> Check the item before you pay.</li>
          <li><i data-lucide="circle-check"></i> Confirm transfers in your own banking app.</li>
        </ul>
      </section>
    </aside>
  </div>
@endsection
