{{-- Sidebar shared by every marketplace page. $active = the current page name (e.g. 'main'). --}}
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
  <div class="sidebar__header">
    <a href="main.html" class="brand">
      <img src="{{ asset('marketplace/img/logo.png') }}" alt="" class="brand__logo" width="34" height="34" />
      <span class="brand__text">
        <span class="brand__name">GaMarketplace.</span>
        <span class="brand__tagline">Your Campus, Your Marketplace</span>
      </span>
    </a>

    <button class="sidebar__close icon-btn" id="sidebarClose" type="button"
            aria-label="Close sidebar" aria-controls="sidebar">
      <i data-lucide="panel-left-close"></i>
    </button>
  </div>

  <nav class="sidebar__nav">
    <ul class="nav-list">
      <li>
        <a href="main.html" class="nav-link {{ $active === 'main' ? 'is-active' : '' }}" @if($active === 'main') aria-current="page" @endif>
          <i data-lucide="home"></i><span>Explore</span>
        </a>
      </li>
      <li>
        <a href="marketplace.html" class="nav-link {{ $active === 'marketplace' ? 'is-active' : '' }}" @if($active === 'marketplace') aria-current="page" @endif>
          <i data-lucide="shopping-bag"></i><span>Marketplace</span>
        </a>
      </li>
      <li>
        <a href="bidding.html" class="nav-link {{ $active === 'bidding' ? 'is-active' : '' }}" @if($active === 'bidding') aria-current="page" @endif>
          <i data-lucide="gavel"></i><span>Bidding Arena</span>
        </a>
      </li>
      <li>
        <a href="looking-for.html" class="nav-link {{ $active === 'looking-for' ? 'is-active' : '' }}" @if($active === 'looking-for') aria-current="page" @endif>
          <i data-lucide="search"></i><span>Looking For</span>
        </a>
      </li>
    </ul>

    <hr class="sidebar__divider" />

    <ul class="nav-list">
      <li>
        <a href="scam-watch.html" class="nav-link {{ $active === 'scam-watch' ? 'is-active' : '' }}" @if($active === 'scam-watch') aria-current="page" @endif>
          <i data-lucide="shield-alert"></i><span>Scam Watch</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar__footer">
    <img src="{{ asset('marketplace/img/ugm-emblem.png') }}" alt="UGM emblem" class="sidebar__emblem" width="48" height="48" />
    <p>Exclusively for<br /><strong>UGM Students</strong></p>
  </div>
</aside>

<!-- Dark overlay behind the sidebar on small screens -->
<div class="sidebar-overlay" id="sidebarOverlay" hidden></div>
