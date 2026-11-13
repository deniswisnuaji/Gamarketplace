{{-- Converted from frontend-original/admin.html. --}}

@extends('layouts.marketplace-auth')

@section('title', 'Admin — GaMarketplace')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/admin.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/layout.js') }}" defer></script>
    <script src="{{ asset('marketplace/js/pages/admin.js') }}" defer></script>
@endpush

@section('content')
  <!-- ================= ADMIN SIDEBAR ================= -->
  <aside class="sidebar sidebar--admin" id="sidebar" aria-label="Admin navigation">
    <div class="sidebar__header">
      <a href="#dashboard" class="brand">
        <img src="{{ asset('marketplace/img/logo.png') }}" alt="" class="brand__logo" width="34" height="34" onerror="this.remove()" />
        <span class="brand__text">
          <span class="brand__name">GaMarketplace.</span>
          <span class="brand__tagline"><span class="admin-chip">Admin Panel</span></span>
        </span>
      </a>
      <button class="sidebar__close icon-btn" id="sidebarClose" type="button" aria-label="Close sidebar" aria-controls="sidebar">
        <i data-lucide="panel-left-close"></i>
      </button>
    </div>

    <nav class="sidebar__nav">
      <ul class="nav-list">
        <li><a href="#dashboard" class="nav-link" data-nav="dashboard"><i data-lucide="layout-dashboard"></i><span>Dashboard</span></a></li>
        <li><a href="#review" class="nav-link" data-nav="review"><i data-lucide="clipboard-check"></i><span>Listing Review</span><b class="nav-count" id="navReview">0</b></a></li>
        <li><a href="#listings" class="nav-link" data-nav="listings"><i data-lucide="package"></i><span>All Listings</span></a></li>
        <li><a href="#reports" class="nav-link" data-nav="reports"><i data-lucide="shield-alert"></i><span>Scam Reports</span><b class="nav-count" id="navReports">0</b></a></li>
        <li><a href="#users" class="nav-link" data-nav="users"><i data-lucide="users"></i><span>Users</span></a></li>
      </ul>
      <hr class="sidebar__divider" />
      <ul class="nav-list">
        <li><a href="main.html" class="nav-link" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>View Site</span></a></li>
        <li><button type="button" class="nav-link nav-link--btn" id="adminLogout"><i data-lucide="log-out"></i><span>Log out</span></button></li>
      </ul>
    </nav>

    <div class="sidebar__footer">
      <p>Signed in as<br /><strong id="adminNameSide">Admin</strong></p>
    </div>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay" hidden></div>

  <!-- ================= PAGE ================= -->
  <div class="page">
    <header class="topbar">
      <button class="sidebar__open icon-btn" id="sidebarOpen" type="button" aria-label="Open sidebar" aria-controls="sidebar" aria-expanded="true">
        <i data-lucide="menu"></i>
      </button>
      <h1 class="admin-title" id="pageTitle">Dashboard</h1>
      <div class="topbar__actions">
        <span class="admin-chip admin-chip--top"><i data-lucide="shield"></i> Admin</span>
        <div class="profile">
          <button class="profile__toggle" id="profileToggle" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="profileMenu">
            <span class="avatar-lg admin-avatar" id="adminAvatar" aria-hidden="true">A</span>
            <span class="sr-only">Admin menu</span>
            <i data-lucide="chevron-down"></i>
          </button>
          <ul class="profile__menu" id="profileMenu" role="menu" hidden>
            <li role="none" class="profile__who"><strong id="adminNameMenu">Admin</strong><span id="adminEmailMenu"></span></li>
            <li role="none"><a role="menuitem" href="main.html" target="_blank" rel="noopener">View site</a></li>
            <li role="none"><a role="menuitem" href="#" id="adminLogout2">Log out</a></li>
          </ul>
        </div>
      </div>
    </header>

    <main class="admin-main" id="main">
      <!-- ============ DASHBOARD ============ -->
      <section class="admin-view" data-view="dashboard" aria-labelledby="pageTitle">
        <div class="kpis">
          <a href="#review" class="kpi kpi--yellow"><i data-lucide="hourglass"></i><div><strong id="kPending">0</strong><span>Listings waiting for review</span></div></a>
          <a href="#listings" class="kpi kpi--green"><i data-lucide="circle-check"></i><div><strong id="kLive">0</strong><span>Live listings</span></div></a>
          <a href="#reports" class="kpi kpi--red"><i data-lucide="shield-alert"></i><div><strong id="kReports">0</strong><span>Open scam reports</span></div></a>
          <a href="#users" class="kpi kpi--blue"><i data-lucide="users"></i><div><strong id="kUsers">0</strong><span>Registered users</span></div></a>
        </div>

        <div class="dash-grid">
          <section class="panel">
            <div class="panel__row">
              <h2 class="panel__title"><i data-lucide="bar-chart-3" class="ic-blue"></i> New listings (last 7 days)</h2>
            </div>
            <div class="bars" id="bars" role="img" aria-label="New listings per day"></div>
          </section>

          <section class="panel">
            <div class="panel__row">
              <h2 class="panel__title"><i data-lucide="bell-ring" class="ic-red"></i> Needs attention</h2>
            </div>
            <ul class="attention" id="attention"></ul>
          </section>

          <section class="panel dash-grid__wide">
            <div class="panel__row">
              <h2 class="panel__title"><i data-lucide="history" class="ic-green"></i> Admin activity</h2>
              <span class="hint">This session</span>
            </div>
            <ul class="log" id="activityLog"></ul>
          </section>
        </div>
      </section>

      <!-- ============ LISTING REVIEW ============ -->
      <section class="admin-view" data-view="review" hidden>
        <div class="view-head">
          <p>Approve listings that follow the rules. Rejected listings go back to the seller with your note.</p>
        </div>
        <div class="bid-toolbar admin-toolbar">
          <label class="field field--search">
            <i data-lucide="search"></i><span class="sr-only">Search pending listings</span>
            <input type="search" id="rvSearch" placeholder="Search title or seller" autocomplete="off" />
          </label>
          <label class="field field--select">
            <i data-lucide="layers"></i><span class="sr-only">Type</span>
            <select id="rvType"><option value="">All types</option><option value="marketplace">Marketplace</option><option value="auction">Auction</option></select>
            <i data-lucide="chevron-down" class="field__chevron"></i>
          </label>
        </div>
        <div class="bulk-bar" id="bulkBar" hidden>
          <span><strong id="bulkCount">0</strong> selected</span>
          <button type="button" class="btn btn--primary btn--sm btn--keep" id="bulkApprove"><i data-lucide="check"></i><span>Approve selected</span></button>
          <button type="button" class="btn btn--outline btn--sm" id="bulkClear">Clear</button>
        </div>
        <ul class="review-list" id="reviewList"></ul>
        <div class="empty-state" id="reviewEmpty" hidden>
          <i data-lucide="party-popper" class="empty-icon"></i>
          <p><strong>Queue is empty 🎉</strong></p>
          <p>No listings are waiting for review.</p>
        </div>
      </section>

      <!-- ============ ALL LISTINGS ============ -->
      <section class="admin-view" data-view="listings" hidden>
        <div class="bid-toolbar admin-toolbar admin-toolbar--3">
          <label class="field field--search">
            <i data-lucide="search"></i><span class="sr-only">Search listings</span>
            <input type="search" id="alSearch" placeholder="Search title, seller or tikum" autocomplete="off" />
          </label>
          <label class="field field--select">
            <i data-lucide="filter"></i><span class="sr-only">Status</span>
            <select id="alStatus"><option value="">All statuses</option><option value="approved">Live</option><option value="pending">In review</option><option value="rejected">Rejected</option><option value="hidden">Hidden</option></select>
            <i data-lucide="chevron-down" class="field__chevron"></i>
          </label>
          <label class="field field--select">
            <i data-lucide="layers"></i><span class="sr-only">Type</span>
            <select id="alType"><option value="">All types</option><option value="marketplace">Marketplace</option><option value="auction">Auction</option></select>
            <i data-lucide="chevron-down" class="field__chevron"></i>
          </label>
        </div>
        <div class="table-wrap">
          <table class="admin-table" id="listingTable">
            <thead>
              <tr><th scope="col">Item</th><th scope="col">Seller</th><th scope="col">Type</th><th scope="col">Price</th><th scope="col">Status</th><th scope="col">Availability</th><th scope="col">Saves</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody id="listingBody"></tbody>
          </table>
        </div>
        <p class="hint" id="listingCount"></p>
      </section>

      <!-- ============ SCAM REPORTS ============ -->
      <section class="admin-view" data-view="reports" hidden>
        <div class="view-head">
          <p>Verify real scams so they appear on Scam Watch, or dismiss reports that aren't scams. Reporter names stay private.</p>
        </div>
        <div class="status-tabs" role="tablist" aria-label="Report status">
          <button type="button" class="status-tab is-active" role="tab" aria-selected="true" data-rtab="review"><span>Under review (<span data-rcount="review">0</span>)</span></button>
          <button type="button" class="status-tab" role="tab" aria-selected="false" data-rtab="verified"><span>Verified (<span data-rcount="verified">0</span>)</span></button>
          <button type="button" class="status-tab" role="tab" aria-selected="false" data-rtab="dismissed"><span>Dismissed (<span data-rcount="dismissed">0</span>)</span></button>
        </div>
        <ul class="report-list" id="reportList"></ul>
        <div class="empty-state" id="reportEmpty" hidden><p><strong>Nothing here.</strong></p></div>
      </section>

      <!-- ============ USERS ============ -->
      <section class="admin-view" data-view="users" hidden>
        <div class="bid-toolbar admin-toolbar">
          <label class="field field--search">
            <i data-lucide="search"></i><span class="sr-only">Search users</span>
            <input type="search" id="usSearch" placeholder="Search name, email or faculty" autocomplete="off" />
          </label>
          <label class="field field--select">
            <i data-lucide="filter"></i><span class="sr-only">Account status</span>
            <select id="usStatus"><option value="">All users</option><option value="active">Active</option><option value="suspended">Suspended</option><option value="flagged">Has reports</option></select>
            <i data-lucide="chevron-down" class="field__chevron"></i>
          </label>
        </div>
        <div class="table-wrap">
          <table class="admin-table" id="userTable">
            <thead>
              <tr><th scope="col">User</th><th scope="col">Faculty</th><th scope="col">Joined</th><th scope="col">Listings</th><th scope="col">Reports</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody id="userBody"></tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <!-- ================= DIALOGS ================= -->
  <!-- Reject -->
  <dialog class="bid-dialog form-dialog" id="rejectDialog" aria-labelledby="rjTitle">
    <form method="dialog" class="form-dialog__inner" id="rejectForm" novalidate>
      <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close"><i data-lucide="x"></i></button>
      <h2 id="rjTitle">Reject listing</h2>
      <p class="form-dialog__sub" id="rjItem"></p>
      <p class="hint">The seller gets a notification with your reason and note.</p>
      <span class="bx-label" id="rjReasonLabel">Reason</span>
      <div class="reason-list" role="radiogroup" aria-labelledby="rjReasonLabel" id="rjReasons"></div>
      <label class="bx-label" for="rjNote">Note to the seller <small>(they will see this)</small></label>
      <textarea id="rjNote" class="text-input" rows="3" maxlength="300" placeholder="Explain what to fix so they can resubmit."></textarea>
      <p class="form-error" id="rjError" role="alert" hidden></p>
      <div class="bid-dialog__actions">
        <button type="button" class="btn btn--outline" data-close>Cancel</button>
        <button type="submit" class="btn btn--danger"><span>Reject listing</span></button>
      </div>
    </form>
  </dialog>

  <!-- Detail / edit -->
  <dialog class="bid-dialog form-dialog detail-dialog" id="detailDialog" aria-labelledby="dtTitle">
    <form method="dialog" class="form-dialog__inner" id="detailForm" novalidate>
      <button type="button" class="icon-btn bid-dialog__close" data-close aria-label="Close"><i data-lucide="x"></i></button>
      <h2 id="dtTitle">Listing details</h2>
      <div class="dt-photos" id="dtPhotos"></div>
      <div class="dt-meta" id="dtMeta"></div>
      <label class="bx-label" for="dtTitleInput">Title</label>
      <input type="text" id="dtTitleInput" class="text-input" maxlength="70" />
      <div class="two-col">
        <div><label class="bx-label" for="dtPrice">Price (Rp)</label><input type="text" id="dtPrice" class="text-input" inputmode="numeric" /></div>
        <div><label class="bx-label" for="dtCategory">Category</label>
          <select id="dtCategory" class="select">
            <option value="education">Education</option><option value="electronics">Electronics</option><option value="furniture">Furniture</option>
            <option value="fashion">Fashion</option><option value="hobbies">Hobbies</option><option value="self-care">Self-Care</option><option value="others">Others</option>
          </select></div>
      </div>
      <label class="bx-label" for="dtDesc">Description</label>
      <textarea id="dtDesc" class="text-input" rows="3" maxlength="800"></textarea>
      <p class="hint">Admins should only fix small mistakes (typos, wrong category). For bigger problems, reject with a note.</p>
      <p class="form-error" id="dtError" role="alert" hidden></p>
      <div class="bid-dialog__actions">
        <button type="button" class="btn btn--outline" data-close>Cancel</button>
        <button type="submit" class="btn btn--primary"><span>Save changes</span></button>
      </div>
    </form>
  </dialog>

  <!-- Confirm (generic) -->
  <dialog class="bid-dialog form-dialog" id="confirmDialog" aria-labelledby="cfTitle">
    <form method="dialog" class="form-dialog__inner" id="confirmForm" novalidate>
      <h2 id="cfTitle">Are you sure?</h2>
      <p class="form-dialog__sub" id="cfText"></p>
      <div id="cfReasonWrap" hidden>
        <label class="bx-label" for="cfReason">Reason <small>(saved in the admin log)</small></label>
        <textarea id="cfReason" class="text-input" rows="2" maxlength="200"></textarea>
        <p class="form-error" id="cfError" role="alert" hidden></p>
      </div>
      <div class="bid-dialog__actions">
        <button type="button" class="btn btn--outline" data-close>Cancel</button>
        <button type="submit" class="btn btn--danger" id="cfOk"><span>Confirm</span></button>
      </div>
    </form>
  </dialog>
@endsection
