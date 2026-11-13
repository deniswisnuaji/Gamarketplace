{{-- Converted from frontend-original/looking-for.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Looking For — GaMarketplace')

@section('description', 'Post what you need and get offers from UGM students.')

@section('active', 'looking-for')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/looking-for.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/looking-for.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT GRID ---------- -->
  <div class="layout">
    <main class="main" id="main">

      <!-- HERO -->
      <section class="hero">
        <div class="hero__content">
          <p class="hero__badge">
            <i data-lucide="search"></i>
            Looking For
          </p>
          <h1 class="hero__title">
            Can’t find it? Ask the
            <span class="highlight">whole campus.</span>
          </h1>
          <p class="hero__subtitle">Post what you need, set your budget, and let fellow UGM students send you offers.</p>
          <button type="button" class="btn btn--primary hero__cta" id="heroPostBtn">
            <i data-lucide="plus"></i><span>Post a Request</span>
          </button>
        </div>
        <img src="{{ asset('marketplace/img/hero-ugm.jpg') }}" alt="Balairung building at UGM" class="hero__image" />
      </section>

      <!-- SEARCH / AREA / SORT -->
      <div class="bid-toolbar">
        <label class="field field--search">
          <i data-lucide="search"></i>
          <span class="sr-only">Search requests</span>
          <input type="search" id="reqSearch" placeholder="Search requests (e.g., calculator, jas lab, kasur)" autocomplete="off" />
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

        <label class="field field--sort">
          <span class="field__label">Sort by</span>
          <select id="sortSelect">
            <option value="newest">Newest</option>
            <option value="soonest">Needed Soonest</option>
            <option value="budget">Highest Budget</option>
            <option value="fewest">Fewest Offers</option>
          </select>
          <i data-lucide="chevrons-up-down" class="field__chevron"></i>
        </label>
      </div>

      <!-- FILTER DROPDOWNS -->
      <div class="filter-row">
        <button type="button" class="pill-btn" id="filterToggle" aria-expanded="true" aria-controls="filterSelects">
          <i data-lucide="sliders-horizontal"></i> Filter
          <span class="pill-count" id="filterCount" hidden>0</span>
        </button>

        <div class="filter-selects" id="filterSelects">
          <label class="pill-select">
            <span class="sr-only">Category</span>
            <select data-filter="category">
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
            <span class="sr-only">Budget</span>
            <select data-filter="budget">
              <option value="">Budget</option>
              <option value="0-100000">Under Rp 100rb</option>
              <option value="100000-500000">Rp 100rb – 500rb</option>
              <option value="500000-">Above Rp 500rb</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <label class="pill-select">
            <span class="sr-only">Faculty</span>
            <select data-filter="faculty">
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
            <span class="sr-only">Condition wanted</span>
            <select data-filter="condition">
              <option value="">Condition Wanted</option>
              <option value="any">Any condition</option>
              <option value="good">Good or better</option>
              <option value="like-new">Like new only</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <button type="button" class="reset-btn" id="resetBtn">
            <i data-lucide="rotate-cw"></i> Reset
          </button>
        </div>
      </div>

      <!-- TABS -->
      <div class="status-tabs" role="tablist" aria-label="Request type">
        <button type="button" class="status-tab is-active" role="tab" aria-selected="true" data-tab="all">
          <span>All Requests (<span data-tab-count="all">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="urgent">
          <span aria-hidden="true">⚡</span>
          <span>Urgent (<span data-tab-count="urgent">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="nooffers">
          <i data-lucide="inbox"></i>
          <span>No Offers Yet (<span data-tab-count="nooffers">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="mine">
          <i data-lucide="user-round"></i>
          <span>My Requests (<span data-tab-count="mine">0</span>)</span>
        </button>
      </div>

      <!-- REQUEST CARDS (rendered by script) -->
      <div class="req-grid" id="reqGrid"></div>

      <div class="empty-state" id="emptyState" hidden>
        <p><strong>No requests match these filters.</strong></p>
        <p>Reset the filters, or post your own request so sellers can find you.</p>
        <div class="empty-state__actions">
          <button type="button" class="btn btn--outline" id="emptyResetBtn">Reset filters</button>
          <button type="button" class="btn btn--primary" id="emptyPostBtn"><i data-lucide="plus"></i><span>Post a Request</span></button>
        </div>
      </div>
    </main>

    <!-- ---------- RIGHT PANEL ---------- -->
    <aside class="rightbar" aria-label="Your requests">
      <!-- My requests -->
      <section class="panel">
        <h2 class="panel__title"><span class="title-icon"><i data-lucide="user-round"></i></span> My Requests</h2>
        <div class="stats">
          <div class="stat stat--active">
            <i data-lucide="search"></i>
            <strong id="statActive">0</strong>
            <span>Active</span>
          </div>
          <div class="stat stat--won">
            <i data-lucide="inbox"></i>
            <strong id="statOffers">0</strong>
            <span>Offers</span>
          </div>
          <div class="stat stat--watch">
            <i data-lucide="circle-check"></i>
            <strong id="statFound">0</strong>
            <span>Found</span>
          </div>
        </div>
        <button type="button" class="btn btn--primary btn--block" id="panelPostBtn">
          <i data-lucide="plus"></i><span>Post a Request</span>
        </button>
      </section>

      <!-- Trending -->
      <section class="panel">
        <h2 class="panel__title"><i data-lucide="trending-up" class="icon-trend"></i> Most Wanted This Week</h2>
        <p class="panel__sub">Have one of these? Students are looking for them.</p>
        <div class="trend-chips" id="trendChips">
        </div>
      </section>

      <!-- Activity -->
      <section class="panel">
        <h2 class="panel__title"><i data-lucide="message-square-more" class="icon-msg"></i> Recent Offers</h2>
        <ul class="activity" id="activityList" aria-live="polite"></ul>
        <button type="button" class="btn btn--outline btn--block" id="activityMoreBtn" aria-expanded="false">
          <span>See All Activity</span> <i data-lucide="arrow-right"></i>
        </button>
      </section>
    </aside>
  </div>
@endsection

@section('after_page')
<!-- ================= OFFER DIALOG ("I Have This") ================= -->
  <dialog class="bid-dialog form-dialog" id="offerDialog" aria-labelledby="ofTitle">
    <form method="dialog" class="form-dialog__inner" id="offerForm" novalidate>
      <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close">
        <i data-lucide="x"></i>
      </button>

      <header>
        <span class="cat-tag" id="ofCat"></span>
        <h2 id="ofTitle">Request</h2>
        <p class="bx-meta">
          <span><i data-lucide="user-round"></i> <span id="ofOwner"></span></span>
          <span><i data-lucide="map-pin"></i> <span id="ofPlace"></span></span>
          <span><i data-lucide="calendar"></i> <span id="ofNeeded"></span></span>
        </p>
      </header>

      <div class="req-summary">
        <div class="ref-photos" id="ofPhotos" hidden></div>
        <p id="ofDesc"></p>
        <div class="req-summary__row">
          <div><span>Budget</span><strong id="ofBudget"></strong></div>
          <div><span>Condition</span><strong id="ofCond"></strong></div>
          <div><span>Offers so far</span><strong id="ofCount"></strong></div>
        </div>
      </div>

      <div class="bx-status bx-status--sent" id="ofStatus" hidden></div>

      <fieldset class="bx-form" id="ofFields">
        <legend>Your offer</legend>

        <label class="bx-label" for="ofItem">What you have</label>
        <input type="text" id="ofItem" class="text-input" maxlength="80" placeholder="e.g. Casio FX-991EX, lengkap dengan cover" />

        <div class="two-col">
          <div>
            <label class="bx-label" for="ofPrice">Your price</label>
            <div class="bid-input">
              <span>Rp</span>
              <input type="number" id="ofPrice" inputmode="numeric" min="0" step="5000" />
            </div>
          </div>
          <div>
            <label class="bx-label" for="ofCondition">Condition</label>
            <select id="ofCondition" class="select">
              <option value="like-new">Like New</option>
              <option value="good" selected>Good</option>
              <option value="fair">Fair</option>
            </select>
          </div>
        </div>
        <p class="hint" id="ofPriceHint" hidden></p>

        <label class="bx-label" for="ofMsg">Message <small>(optional)</small></label>
        <textarea id="ofMsg" class="text-input" rows="2" maxlength="200" placeholder="Bisa COD di Perpustakaan Pusat besok siang."></textarea>

        <p class="bid-error" id="ofError" role="alert" hidden></p>
      </fieldset>

      <div class="bid-dialog__actions">
        <button type="submit" class="btn btn--primary" id="ofSubmit">
          <i data-lucide="send"></i><span>Send Offer</span>
        </button>
        <a href="messages.html" class="btn btn--outline">
          <i data-lucide="message-circle-more"></i><span>Chat</span>
        </a>
      </div>
    </form>
  </dialog>

  <!-- ================= POST REQUEST DIALOG ================= -->
  <dialog class="bid-dialog form-dialog" id="postDialog" aria-labelledby="postTitle">
    <form method="dialog" class="form-dialog__inner" id="postForm" novalidate>
      <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close">
        <i data-lucide="x"></i>
      </button>

      <header>
        <h2 id="postTitle">Post a Request</h2>
        <p class="form-dialog__sub">Tell students what you’re looking for. They’ll send you offers.</p>
      </header>

      <fieldset class="bx-form">
        <legend class="sr-only">Request details</legend>

        <label class="bx-label" for="pTitle">What are you looking for?</label>
        <input type="text" id="pTitle" class="text-input" maxlength="70" placeholder="e.g. Kalkulator Casio FX-991EX" required />

        <div class="two-col">
          <div>
            <label class="bx-label" for="pCategory">Category</label>
            <select id="pCategory" class="select">
              <option value="education">Education</option>
              <option value="electronics">Electronics</option>
              <option value="furniture">Furniture</option>
              <option value="fashion">Fashion</option>
              <option value="hobbies">Hobbies</option>
              <option value="self-care">Self-Care</option>
              <option value="others">Others</option>
            </select>
          </div>
          <div>
            <label class="bx-label" for="pCondition">Condition wanted</label>
            <select id="pCondition" class="select">
              <option value="any">Any condition</option>
              <option value="good">Good or better</option>
              <option value="like-new">Like new only</option>
            </select>
          </div>
        </div>

        <div class="two-col">
          <div>
            <label class="bx-label" for="pMin">Budget from</label>
            <div class="bid-input"><span>Rp</span><input type="number" id="pMin" inputmode="numeric" min="0" step="5000" placeholder="50000" /></div>
          </div>
          <div>
            <label class="bx-label" for="pMax">Budget up to</label>
            <div class="bid-input"><span>Rp</span><input type="number" id="pMax" inputmode="numeric" min="0" step="5000" placeholder="150000" /></div>
          </div>
        </div>

        <div class="two-col">
          <div>
            <label class="bx-label" for="pFaculty">Your faculty</label>
            <select id="pFaculty" class="select">
              <option value="" selected disabled>Choose your faculty</option>
              <option>Biologi</option>
              <option>FEB</option>
              <option>Farmasi</option>
              <option>Filsafat</option>
              <option>Geografi</option>
              <option>FH</option>
              <option>FIB</option>
              <option>FISIPOL</option>
              <option>FK-KMK</option>
              <option>FKG</option>
              <option>FKH</option>
              <option>FKT</option>
              <option>FMIPA</option>
              <option>FAPERTA</option>
              <option>FAPET</option>
              <option>Psikologi</option>
              <option>FT</option>
              <option>FTP</option>
              <option>SV</option>
              <option>SPs</option>
            </select>
          </div>
          <div>
            <label class="bx-label" for="pDays">Needed by</label>
            <select id="pDays" class="select">
              <option value="2">Within 2 days</option>
              <option value="7" selected>Within a week</option>
              <option value="14">Within 2 weeks</option>
              <option value="30">No rush</option>
            </select>
          </div>
        </div>

        <label class="bx-label" for="pArea">Suggested meet-up point (tikum)</label>
        <select id="pArea" class="select">
          <option value="" selected disabled>Choose a meet-up point</option>
          <option>Biologi</option>
          <option>FEB</option>
          <option>Farmasi</option>
          <option>Filsafat</option>
          <option>Geografi</option>
          <option>FH</option>
          <option>FIB</option>
          <option>FISIPOL</option>
          <option>FK-KMK</option>
          <option>FKG</option>
          <option>FKH</option>
          <option>FKT</option>
          <option>FMIPA</option>
          <option>FAPERTA</option>
          <option>FAPET</option>
          <option>Psikologi</option>
          <option>FT</option>
          <option>FTP</option>
          <option>SV</option>
          <option>SPs</option>
        </select>

        <label class="bx-label" for="pDesc">Details <small>(optional)</small></label>
        <textarea id="pDesc" class="text-input" rows="2" maxlength="200" placeholder="Brand, size, edition, or anything sellers should know."></textarea>

        <span class="bx-label" id="pPhotosLabel">Reference photo <small>(optional, up to 3)</small></span>
        <label class="dropzone" id="photoDrop" for="pPhotos">
          <i data-lucide="image-plus"></i>
          <span><strong>Click to upload</strong> or drag photos here</span>
          <small>Show sellers what it looks like · JPG, PNG or WEBP · max 5 MB each</small>
          <input type="file" id="pPhotos" class="sr-only" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="pPhotosLabel" />
        </label>
        <div class="photo-previews" id="photoPreviews"></div>

        <p class="bid-error" id="pError" role="alert" hidden></p>
      </fieldset>

      <div class="bid-dialog__actions">
        <button type="submit" class="btn btn--primary">
          <i data-lucide="send"></i><span>Post Request</span>
        </button>
        <button type="button" class="btn btn--outline" data-close>Cancel</button>
      </div>
    </form>
  </dialog>

  <!-- ================= PHOTO LIGHTBOX ================= -->
  <dialog class="lightbox" id="lightbox" aria-label="Reference photo">
    <button type="button" class="icon-btn lightbox__close" data-close aria-label="Close photo">
      <i data-lucide="x"></i>
    </button>
    <button type="button" class="lightbox__nav lightbox__nav--prev" id="lbPrev" aria-label="Previous photo">
      <i data-lucide="chevron-left"></i>
    </button>
    <img id="lbImg" src="" alt="" />
    <button type="button" class="lightbox__nav lightbox__nav--next" id="lbNext" aria-label="Next photo">
      <i data-lucide="chevron-right"></i>
    </button>
    <p class="lightbox__count" id="lbCount"></p>
  </dialog>
@endsection
