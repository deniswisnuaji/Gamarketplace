{{-- Converted from frontend-original/scam-watch.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Scam Watch — GaMarketplace')

@section('description', 'Scam alerts and safety tools for UGM students trading on campus.')

@section('active', 'scam-watch')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/scam-watch.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/scam-watch.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT GRID ---------- -->
  <div class="layout">
    <main class="main" id="main">

      <!-- HERO -->
      <section class="hero">
        <div class="hero__content">
          <p class="hero__badge hero__badge--alert">
            <i data-lucide="shield-alert"></i>
            Scam Watch
          </p>
          <h1 class="hero__title">
            Trade smart. Stay
            <span class="highlight">safe on campus.</span>
          </h1>
          <p class="hero__subtitle">See scam reports from the UGM community, check before you pay, and warn others when something feels off.</p>
          <div class="hero__actions">
            <button type="button" class="btn btn--danger" id="heroReportBtn">
              <i data-lucide="flag"></i><span>Report a Scam</span>
            </button>
            <a href="#checklist" class="btn btn--outline">
              <i data-lucide="list-checks"></i><span>Safety Checklist</span>
            </a>
          </div>
        </div>
        <img src="{{ asset('marketplace/img/hero-ugm.jpg') }}" alt="Balairung building at UGM" class="hero__image" />
      </section>

      <!-- QUICK STATS (computed from the alerts) -->
      <section class="sw-stats" aria-label="Scam Watch summary">
        <div class="sw-stat sw-stat--red">
          <i data-lucide="triangle-alert"></i>
          <div><strong id="statHigh">0</strong><span>High-risk alerts</span></div>
        </div>
        <div class="sw-stat sw-stat--green">
          <i data-lucide="badge-check"></i>
          <div><strong id="statVerified">0</strong><span>Verified by admin</span></div>
        </div>
        <div class="sw-stat sw-stat--blue">
          <i data-lucide="calendar-clock"></i>
          <div><strong id="statWeek">0</strong><span>Reported this week</span></div>
        </div>
        <div class="sw-stat sw-stat--yellow">
          <i data-lucide="users"></i>
          <div><strong id="statSeen">0</strong><span>“I saw this too”</span></div>
        </div>
      </section>

      <!-- SEARCH / SORT -->
      <div class="bid-toolbar sw-toolbar">
        <label class="field field--search">
          <i data-lucide="search"></i>
          <span class="sr-only">Search alerts</span>
          <input type="search" id="alertSearch" placeholder="Search alerts (e.g., transfer, COD, link, OTP)" autocomplete="off" />
        </label>

        <label class="field field--sort">
          <span class="field__label">Sort by</span>
          <select id="sortSelect">
            <option value="newest">Newest</option>
            <option value="seen">Most Reported</option>
            <option value="severity">Highest Risk</option>
          </select>
          <i data-lucide="chevrons-up-down" class="field__chevron"></i>
        </label>
      </div>

      <!-- FILTERS -->
      <div class="filter-row">
        <button type="button" class="pill-btn" id="filterToggle" aria-expanded="true" aria-controls="filterSelects">
          <i data-lucide="sliders-horizontal"></i> Filter
          <span class="pill-count" id="filterCount" hidden>0</span>
        </button>

        <div class="filter-selects" id="filterSelects">
          <label class="pill-select">
            <span class="sr-only">Scam type</span>
            <select data-filter="type">
              <option value="">Scam Type</option>
              <option value="payment">Fake Payment Proof</option>
              <option value="noshow">No Show / Ghosting</option>
              <option value="phishing">Phishing Link</option>
              <option value="fake-item">Fake / Wrong Item</option>
              <option value="impersonation">Impersonation</option>
              <option value="deposit">Deposit (DP) Scam</option>
              <option value="other">Other</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <label class="pill-select">
            <span class="sr-only">Risk level</span>
            <select data-filter="severity">
              <option value="">Risk Level</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <label class="pill-select">
            <span class="sr-only">Where it happened</span>
            <select data-filter="channel">
              <option value="">Where</option>
              <option value="online">Online / chat</option>
              <option value="meetup">At a meet-up</option>
            </select>
            <i data-lucide="chevron-down"></i>
          </label>
          <button type="button" class="reset-btn" id="resetBtn">
            <i data-lucide="rotate-cw"></i> Reset
          </button>
        </div>
      </div>

      <!-- TABS -->
      <div class="status-tabs" role="tablist" aria-label="Alert status">
        <button type="button" class="status-tab is-active" role="tab" aria-selected="true" data-tab="all">
          <span>All Alerts (<span data-tab-count="all">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="verified">
          <i data-lucide="badge-check"></i>
          <span>Verified (<span data-tab-count="verified">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="review">
          <i data-lucide="hourglass"></i>
          <span>Under Review (<span data-tab-count="review">0</span>)</span>
        </button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="mine">
          <i data-lucide="flag"></i>
          <span>My Reports (<span data-tab-count="mine">0</span>)</span>
        </button>
      </div>

      <!-- ALERTS (rendered by script) -->
      <div class="alert-list" id="alertList"></div>

      <div class="empty-state" id="emptyState" hidden>
        <p><strong>No alerts match these filters.</strong></p>
        <p>That's good news — but stay careful. Reset the filters to see all alerts.</p>
        <button type="button" class="btn btn--outline" id="emptyResetBtn">Reset filters</button>
      </div>
    </main>

    <!-- ---------- RIGHT PANEL ---------- -->
    <aside class="rightbar" aria-label="Safety tools">
      <!-- Check before you pay -->
      <section class="panel checker">
        <h2 class="panel__title"><span class="title-icon title-icon--red"><i data-lucide="search-check"></i></span> Check Before You Pay</h2>
        <p class="panel__sub">Paste a phone number, bank account, or link to see if it has been reported.</p>
        <form class="checker__form" id="checkForm">
          <label for="checkInput" class="sr-only">Phone number, account, or link</label>
          <input type="text" id="checkInput" class="text-input" placeholder="e.g. 0812-0000-1111 or a link" autocomplete="off" />
          <button type="submit" class="btn btn--primary"><span>Check</span></button>
        </form>
        <div class="checker__result" id="checkResult" aria-live="polite" hidden></div>
        <p class="checker__note">Checks against scam reports verified by GaMarketplace admins.</p>
      </section>

      <!-- Checklist -->
      <section class="panel" id="checklist">
        <div class="panel__row">
          <h2 class="panel__title"><i data-lucide="list-checks" class="icon-check"></i> Safety Checklist</h2>
          <span class="progress-text" id="checklistText">0/6</span>
        </div>
        <div class="progress" aria-hidden="true"><div class="progress__bar" id="checklistBar"></div></div>
        <ul class="checklist" id="checklistItems">
          <li><label><input type="checkbox" /> <span>Meet in a public spot on campus (Perpustakaan, Student Center).</span></label></li>
          <li><label><input type="checkbox" /> <span>Check the item in person before paying.</span></label></li>
          <li><label><input type="checkbox" /> <span>Confirm the transfer in <strong>your own</strong> banking app, not a screenshot.</span></label></li>
          <li><label><input type="checkbox" /> <span>Never share OTP codes or your password.</span></label></li>
          <li><label><input type="checkbox" /> <span>Don't click links sent by strangers to "verify" your account.</span></label></li>
          <li><label><input type="checkbox" /> <span>Avoid paying a deposit (DP) before seeing the item.</span></label></li>
        </ul>
      </section>

      <!-- Help -->
      <section class="panel help">
        <h2 class="panel__title"><i data-lucide="life-buoy" class="icon-help"></i> Got Scammed?</h2>
        <ol class="help__steps">
          <li>Stop all contact and don't send more money.</li>
          <li>Save evidence: chats, transfer receipts, profile links.</li>
          <li>Contact your bank right away to report the transfer.</li>
          <li>Report it here so other students are warned.</li>
        </ol>
        <button type="button" class="btn btn--danger btn--block" id="panelReportBtn">
          <i data-lucide="flag"></i><span>Report a Scam</span>
        </button>
        <p class="help__emergency"><i data-lucide="phone"></i> In danger right now? Call the police at <a href="tel:110"><strong>110</strong></a>.</p>
      </section>
    </aside>
  </div>
@endsection

@section('after_page')
<!-- ================= REPORT DIALOG ================= -->
  <dialog class="bid-dialog form-dialog" id="reportDialog" aria-labelledby="reportTitle">
    <form method="dialog" class="form-dialog__inner" id="reportForm" novalidate>
      <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close">
        <i data-lucide="x"></i>
      </button>

      <header>
        <h2 id="reportTitle">Report a Scam</h2>
        <p class="form-dialog__sub">Admins review every report before it's shown as verified. Don't post personal info like full names or home addresses.</p>
      </header>

      <fieldset class="bx-form">
        <legend class="sr-only">Report details</legend>

        <div class="two-col">
          <div>
            <label class="bx-label" for="rType">Scam type</label>
            <select id="rType" class="select">
              <option value="payment">Fake Payment Proof</option>
              <option value="noshow">No Show / Ghosting</option>
              <option value="phishing">Phishing Link</option>
              <option value="fake-item">Fake / Wrong Item</option>
              <option value="impersonation">Impersonation</option>
              <option value="deposit">Deposit (DP) Scam</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div>
            <label class="bx-label" for="rChannel">Where did it happen?</label>
            <select id="rChannel" class="select">
              <option value="online">Online / chat</option>
              <option value="meetup">At a meet-up</option>
            </select>
          </div>
        </div>

        <label class="bx-label" for="rTitle">Short title</label>
        <input type="text" id="rTitle" class="text-input" maxlength="70" placeholder="e.g. Buyer sent an edited transfer receipt" />

        <label class="bx-label" for="rDesc">What happened?</label>
        <textarea id="rDesc" class="text-input" rows="3" maxlength="500" placeholder="Explain step by step so others can recognise the pattern."></textarea>
        <span class="char-count" id="rDescCount">0/500</span>

        <div class="two-col">
          <div>
            <label class="bx-label" for="rPlace">Meet-up point / faculty</label>
            <select id="rPlace" class="select">
              <option>Online</option>
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
            <label class="bx-label" for="rDate">When?</label>
            <input type="date" id="rDate" class="text-input" />
          </div>
        </div>

        <label class="bx-label" for="rIdentifier">Phone / account / link used by the scammer <small>(optional, only shown to admins)</small></label>
        <input type="text" id="rIdentifier" class="text-input" maxlength="80" placeholder="e.g. 0812-0000-1111" />

        <label class="bx-label" for="rEvidence">Evidence <small>(up to 3 screenshots, optional — only admins see them)</small></label>
        <input type="file" id="rEvidence" class="file-input" accept="image/*" multiple />
        <div class="evidence-preview" id="evidencePreview"></div>

        <label class="check-row">
          <input type="checkbox" id="rAnon" checked />
          Post anonymously (your name is hidden from other students)
        </label>

        <p class="bid-error" id="rError" role="alert" hidden></p>
      </fieldset>

      <div class="bid-dialog__actions">
        <button type="submit" class="btn btn--danger">
          <i data-lucide="send"></i><span>Submit Report</span>
        </button>
        <button type="button" class="btn btn--outline" data-close>Cancel</button>
      </div>
    </form>
  </dialog>
@endsection
