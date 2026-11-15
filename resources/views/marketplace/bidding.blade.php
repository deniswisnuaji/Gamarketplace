{{-- Converted from frontend-original/bidding.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Bidding Arena — GaMarketplace')

@section('description', 'Join campus auctions and bid on secondhand items from UGM students.')

@section('active', 'bidding')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/bidding.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/price-popover.js') }}" defer></script>
    <script src="{{ asset('marketplace/js/pages/bidding.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT GRID ---------- -->
  <div class="layout">
    <main class="main" id="main">

      <!-- HERO -->
      <section class="hero">
        <div class="hero__content">
          <p class="hero__badge">
            <i data-lucide="gavel"></i>
            Bidding Arena
          </p>
          <h1 class="hero__title">
            Bid, Compete, and Get the
            <span class="highlight">Best Deals.</span>
          </h1>
          <p class="hero__subtitle">Join campus auctions, place your bid, and win secondhand items from fellow UGM students.</p>
        </div>
        <img src="{{ asset('marketplace/img/hero-ugm.jpg') }}" alt="Balairung building at UGM" class="hero__image" />
      </section>

      <!-- SEARCH / AREA / SORT -->
      <div class="bid-toolbar">
        <label class="field field--search">
          <i data-lucide="search"></i>
          <span class="sr-only">Search auction items</span>
          <input type="search" id="auctionSearch" placeholder="Search auction items (e.g., laptop, camera, book)" autocomplete="off" />
        </label>

        <label class="field field--select">
          <i data-lucide="map-pin"></i>
          <span class="sr-only">Meet-up point (tikum)</span>
          <select id="areaSelect">
            <option value="">All Meet-up Points</option>
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
          <i data-lucide="chevron-down" class="field__chevron"></i>
        </label>

        <div class="field field--sort">
          <label for="sortSelect" class="field__label">Sort by</label>
          <select id="sortSelect">
            <option value="ending">Ending Soon</option>
            <option value="bids">Most Bids</option>
            <option value="price">Current Bid</option>
            <option value="newest">Newly Listed</option>
          </select>
          <button type="button" class="sort-dir" id="sortDir" aria-label="Sort direction: ascending" data-dir="asc">
            <i data-lucide="arrow-up"></i>
          </button>
        </div>
      </div>

      <!-- FILTER DROPDOWNS -->
      <div class="filter-row" id="filterRow">
        <button type="button" class="pill-btn" id="filterToggle" aria-expanded="true" aria-controls="filterSelects">
          <i data-lucide="sliders-horizontal"></i> Filter
          <span class="pill-count" id="filterCount" hidden>0</span>
        </button>

        <div class="filter-selects" id="filterSelects">
          <label class="pill-select">
            <span class="sr-only">Category</span>
            <select id="categorySelect" data-filter="category">
              <option value="">Category</option>
              <option value="education">Education</option>
              <option value="electronics">Electronics</option>
              <option value="furniture">Furniture</option>
              <option value="fashion">Fashion</option>
              <option value="hobbies">Hobbies</option>
              <option value="self-care">Self-Care</option>
              <option value="others">Others</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <label class="pill-select">
            <span class="sr-only">Condition</span>
            <select id="conditionSelect" data-filter="condition">
              <option value="">Condition</option>
              <option value="like-new">Like New</option>
              <option value="good">Good</option>
              <option value="fair">Fair</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <div class="price-pop" id="pricePop">
            <button type="button" class="price-pop__btn" id="priceBtn" aria-expanded="false" aria-controls="pricePanel" aria-haspopup="dialog">
              <span class="sr-only">Price range:</span>
              <span id="priceBtnText">Price Range</span>
              <i data-lucide="chevron-down"></i>
            </button>
            <div class="price-pop__panel" id="pricePanel" role="dialog" aria-label="Set price range" hidden>
              <p class="price-pop__title">Set your price range</p>
              <div class="price-pop__inputs">
                <label>
                  <span>Min price</span>
                  <div class="money-input"><span>Rp</span><input type="text" id="popMin" inputmode="numeric" placeholder="0" autocomplete="off" /></div>
                </label>
                <span class="price-pop__dash" aria-hidden="true">–</span>
                <label>
                  <span>Max price</span>
                  <div class="money-input"><span>Rp</span><input type="text" id="popMax" inputmode="numeric" placeholder="No limit" autocomplete="off" /></div>
                </label>
              </div>
              <div class="price-pop__presets" aria-label="Quick ranges">
              <button type="button" data-preset="-100000">Under 100rb</button>
              <button type="button" data-preset="100000-1000000">100rb – 1jt</button>
              <button type="button" data-preset="1000000-5000000">1jt – 5jt</button>
              <button type="button" data-preset="5000000-">Above 5jt</button>
              </div>
              <p class="price-pop__error" id="popError" role="alert" hidden></p>
              <div class="price-pop__actions">
                <button type="button" class="btn btn--outline" data-clear>Clear</button>
                <button type="button" class="btn btn--primary" data-apply><span>Apply</span></button>
              </div>
            </div>
          </div>
          <label class="pill-select">
            <span class="sr-only">Faculty</span>
            <select id="facultySelect" data-filter="faculty">
              <option value="">Faculty</option>
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
            <i data-lucide="chevron-down"></i>
          </label>
          <label class="pill-select">
            <span class="sr-only">Bidding type</span>
            <select id="typeSelect" data-filter="type">
              <option value="">Bidding Type</option>
              <option value="standard">Standard auction</option>
              <option value="buy-now">With Buy Now</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <button type="button" class="reset-btn" id="resetBtn">
            <i data-lucide="rotate-cw"></i> Reset
          </button>
        </div>
      </div>

      <!-- STATUS TABS -->
      <div class="status-tabs" role="tablist" aria-label="Auction status">
        <button type="button" class="status-tab is-active" role="tab" aria-selected="true" data-tab="all">
          <span>All Auctions (<span data-tab-count="all">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="live">
          <span aria-hidden="true">🔥</span> <span>Live Now (<span data-tab-count="live">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="ending">
          <i data-lucide="clock"></i> <span>Ending Soon (<span data-tab-count="ending">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="most">
          <span aria-hidden="true">⭐</span> Most Bids
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="new">
          <i data-lucide="gavel"></i> Newly Listed
        </button>
      </div>

      <p class="mine-banner" id="mineBanner" hidden>
        Showing only auctions you've bid on.
        <button type="button" class="link-btn" id="mineOffBtn">Show all auctions</button>
      </p>

      <!-- AUCTION CARDS (rendered by script) -->
      <div class="bid-grid" id="bidGrid"></div>

      <div class="empty-state" id="emptyState" hidden>
        <p><strong>No auctions match these filters.</strong></p>
        <p>Try another category or faculty, or reset the filters.</p>
        <button type="button" class="btn btn--outline" id="emptyResetBtn">Reset filters</button>
      </div>
    </main>

    <!-- ---------- RIGHT PANEL ---------- -->
    <aside class="rightbar" aria-label="Your bidding">
      <!-- My bidding -->
      <section class="panel">
        <h2 class="panel__title"><span class="title-icon"><i data-lucide="user-round"></i></span> My Bidding</h2>
        <div class="stats">
          <div class="stat stat--active">
            <i data-lucide="gavel"></i>
            <strong id="statActive">0</strong>
            <span>Active Bids</span>
          </div>
          <div class="stat stat--won">
            <i data-lucide="trophy"></i>
            <strong id="statWon">0</strong>
            <span>Won</span>
          </div>
          <div class="stat stat--watch">
            <i data-lucide="bookmark"></i>
            <strong id="statWatching">0</strong>
            <span>Watching</span>
          </div>
        </div>
        <button type="button" class="btn btn--outline btn--block" id="viewMyBidsBtn">
          <span>View My Bids</span> <i data-lucide="arrow-right"></i>
        </button>
      </section>

      <!-- Tips -->
      <section class="panel">
        <h2 class="panel__title"><i data-lucide="lightbulb" class="icon-bulb"></i> Bidding Tips</h2>
        <ul class="tips">
          <li><i data-lucide="circle-check"></i> Cek deskripsi dan kondisi barang dengan teliti.</li>
          <li><i data-lucide="circle-check"></i> Perhatikan lokasi dan opsi pengambilan.</li>
          <li><i data-lucide="circle-check"></i> Pasang batas maksimal (max bid) sesuai anggaran.</li>
          <li><i data-lucide="circle-check"></i> Gunakan chat untuk konfirmasi dengan penjual setelah menang.</li>
        </ul>
      </section>

      <!-- Activity -->
      <section class="panel">
        <h2 class="panel__title"><i data-lucide="trending-up"></i> Recent Bidding Activity</h2>
        <ul class="activity" id="activityList" aria-live="polite"></ul>
        <button type="button" class="btn btn--outline btn--block" id="activityMoreBtn" aria-expanded="false">
          <span>See All Activity</span> <i data-lucide="arrow-right"></i>
        </button>
      </section>
    </aside>
  </div>
@endsection

@section('after_page')
<!-- ================= BID DIALOG ================= -->
  <dialog class="bid-dialog" id="bidDialog" aria-labelledby="bidDialogTitle">
    <form method="dialog" class="bid-dialog__inner" id="bidForm" novalidate>
      <button type="button" class="icon-btn bid-dialog__close" id="bidDialogClose" aria-label="Close">
        <i data-lucide="x"></i>
      </button>

      <div class="bid-dialog__media">
        <img src="" alt="" id="bdImg" />
      </div>

      <div class="bid-dialog__body">
        <h2 id="bidDialogTitle">Item</h2>
        <p class="bid-dialog__desc" id="bdDesc"></p>
        <p class="bid-dialog__meta">
          <span><i data-lucide="map-pin"></i> <span id="bdFaculty"></span></span>
          <span><i data-lucide="user-round"></i> Seller: <span id="bdSeller"></span></span>
        </p>

        <div class="bid-dialog__stats">
          <div><span>Current Bid</span><strong id="bdCurrent"></strong></div>
          <div><span>Bids</span><strong id="bdBids"></strong></div>
          <div><span>Ends in</span><strong id="bdEnds" class="danger"></strong></div>
        </div>

        <p class="bid-status" id="bdStatus" hidden></p>

        <label for="bidAmount" class="bid-dialog__label">
          Your bid <small id="bdMinHint"></small>
        </label>
        <div class="bid-input">
          <span>Rp</span>
          <input type="number" id="bidAmount" inputmode="numeric" min="0" />
        </div>
        <div class="quick-bids" id="quickBids"></div>
        <p class="bid-error" id="bidError" role="alert" hidden></p>

        <div class="bid-dialog__actions">
          <button type="submit" class="btn btn--primary" id="placeBidBtn">
            <i data-lucide="gavel"></i><span>Place Bid</span>
          </button>
          <button type="button" class="btn btn--outline" id="buyNowBtn" hidden></button>
        </div>
        <p class="bid-dialog__note">A bid is a promise to buy. Meet at a safe campus spot to pay and pick up.</p>
      </div>
    </form>
  </dialog>
@endsection
