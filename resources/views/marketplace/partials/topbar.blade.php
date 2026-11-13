{{-- Top bar shared by every marketplace page. --}}
<header class="topbar">
  <button class="sidebar__open icon-btn" id="sidebarOpen" type="button"
          aria-label="Open sidebar" aria-controls="sidebar" aria-expanded="true">
    <i data-lucide="menu"></i>
  </button>

  <form class="search" role="search" @hasSection('search_form_id') id="@yield('search_form_id')" @else action="marketplace.html" method="get" @endif>
    <i data-lucide="search" class="search__icon"></i>
    <label for="@yield('search_id', 'globalSearch')" class="sr-only">Search items</label>
    <input id="@yield('search_id', 'globalSearch')" name="q" type="search"
           placeholder="@yield('search_placeholder', 'Search for textbooks, electronics, kos essentials...')" autocomplete="off" />
  </form>

  {{-- Extra buttons next to the search (e.g. "Use my location" on Marketplace) --}}
  @stack('topbar_after_search')

  <div class="topbar__actions">
    <a href="notifications.html" class="icon-btn has-badge" id="bellLink" aria-label="Notifications (new)">
      <i data-lucide="bell"></i>
      <span class="badge-dot" aria-hidden="true"></span>
    </a>

    <a href="messages.html" class="icon-btn" aria-label="Messages">
      <i data-lucide="message-circle-more"></i>
    </a>

    <div class="profile">
      <button class="profile__toggle" id="profileToggle" type="button"
              aria-haspopup="menu" aria-expanded="false" aria-controls="profileMenu">
        <span class="avatar-lg avatar" style="width:44px;height:44px" aria-hidden="true"></span>{{-- filled with your photo / initials by common.js --}}
        <i data-lucide="chevron-down"></i>
      </button>
      <ul class="profile__menu" id="profileMenu" role="menu" hidden>
        <li role="none"><a role="menuitem" href="profile.html">My profile</a></li>
        <li role="none"><a role="menuitem" href="my-listings.html">My listings</a></li>
        <li role="none"><a role="menuitem" href="wishlist.html">Wishlist</a></li>
        <li role="none"><a role="menuitem" href="settings.html">Settings</a></li>
        <li role="none"><a role="menuitem" href="logout.html">Log out</a></li>
      </ul>
    </div>

    <a href="list-item.html" class="btn btn--primary">
      <i data-lucide="plus"></i><span>List an Item</span>
    </a>
  </div>
</header>
