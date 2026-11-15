{{-- Converted from frontend-original/notifications.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'Notifications — GaMarketplace')

@section('description', 'Your GaMarketplace notifications.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/notifications.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/notifications.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT GRID ---------- -->
  <div class="layout">
    <main class="main" id="main">
      <header class="page-head">
        <div>
          <h1><span class="title-icon"><i data-lucide="bell"></i></span> Notifications</h1>
          <p><strong id="unreadText">0 unread</strong> · offers, bids, messages and safety alerts.</p>
        </div>
        <div class="page-head__actions">
          <button type="button" class="btn btn--outline" id="markAllBtn"><i data-lucide="check-check"></i><span>Mark all as read</span></button>
        </div>
      </header>

      <div class="status-tabs" role="tablist" aria-label="Notification type">
        <button type="button" class="status-tab is-active" role="tab" aria-selected="true" data-tab="all"><span>All</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="unread"><span>Unread (<span data-count="unread">0</span>)</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="trade"><i data-lucide="shopping-bag"></i><span>Buying &amp; Selling</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="bidding"><i data-lucide="gavel"></i><span>Bidding</span></button>
        <button type="button" class="status-tab" role="tab" aria-selected="false" data-tab="safety"><i data-lucide="shield-alert"></i><span>Safety</span></button>
      </div>

      <div id="notifGroups"></div>

      <div class="empty-state" id="emptyState" hidden>
        <p><strong>You're all caught up 🎉</strong></p>
        <p>New notifications will show up here.</p>
      </div>
    </main>

    <aside class="rightbar" aria-label="Notification settings">
      <section class="panel">
        <h2 class="panel__title"><i data-lucide="bell-ring" class="icon-blue"></i> Quick Settings</h2>
        <ul class="toggle-list">
          <li><div><strong>New messages</strong><span>When someone chats you.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifMessages" aria-label="Message notifications" /><span class="switch__track"></span></label></li>
          <li><div><strong>Outbid alerts</strong><span>Know right away when you lose the lead.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifOutbid" aria-label="Outbid alerts" /><span class="switch__track"></span></label></li>
          <li><div><strong>Scam alerts</strong><span>High-risk verified alerts only.</span></div>
            <label class="switch"><input type="checkbox" data-pref="notifScam" aria-label="Scam alerts" /><span class="switch__track"></span></label></li>
        </ul>
        <a href="settings.html#notifications" class="btn btn--outline btn--block"><span>All notification settings</span> <i data-lucide="arrow-right"></i></a>
      </section>
    </aside>
  </div>
@endsection
