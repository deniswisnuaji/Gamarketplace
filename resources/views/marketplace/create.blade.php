{{-- Converted from frontend-original/list-item.html. Keep the markup in sync with the approved design. --}}

@extends('layouts.marketplace')

@section('title', 'List an Item — GaMarketplace')

@section('description', 'Sell or auction your item on GaMarketplace.')

@section('active', '')

@push('styles')
    <link rel="stylesheet" href="{{ asset('marketplace/css/components.css') }}" />
    <link rel="stylesheet" href="{{ asset('marketplace/css/pages/list-item.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('marketplace/js/pages/list-item.js') }}" defer></script>
@endpush

@section('content')
  <!-- ---------- CONTENT GRID ---------- -->
  <div class="layout">
    <main class="main" id="main">
      <header class="page-head">
        <div>
          <h1><span class="title-icon"><i data-lucide="plus"></i></span> List an Item</h1>
          <p>Sell it or auction it. An admin checks every listing before it goes public.</p>
        </div>
        <div class="page-head__actions">
          <span class="draft-status" id="draftStatus" aria-live="polite"></span>
        </div>
      </header>

      <form class="li-form" id="listForm" novalidate>
        <!-- 1. Type -->
        <section class="panel li-step">
          <h2 class="li-step__title"><span class="li-step__num">1</span> How do you want to list it?</h2>
          <div class="type-options" role="radiogroup" aria-label="Listing type">
            <label class="type-option">
              <input type="radio" name="type" value="marketplace" checked />
              <span class="type-option__box">
                <i data-lucide="shopping-bag"></i>
                <strong>Sell</strong>
                <small>Fixed price in Marketplace</small>
              </span>
            </label>
            <label class="type-option">
              <input type="radio" name="type" value="auction" />
              <span class="type-option__box">
                <i data-lucide="gavel"></i>
                <strong>Auction</strong>
                <small>Let students bid in Bidding Arena</small>
              </span>
            </label>
          </div>
        </section>

        <!-- 2. Photos -->
        <section class="panel li-step">
          <h2 class="li-step__title"><span class="li-step__num">2</span> Photos <small>(1–4, the first one is the cover)</small></h2>
          <div class="photo-grid" id="photoGrid">
            <label class="photo-add" id="photoAdd" for="photoInput">
              <i data-lucide="image-plus"></i>
              <span><strong>Add photos</strong><br />or drag them here</span>
              <input type="file" id="photoInput" class="sr-only" accept="image/jpeg,image/png,image/webp" multiple />
            </label>
          </div>
          <p class="hint">JPG, PNG or WEBP · max 5 MB each. Use bright, real photos of your item (no photos from online shops).</p>
          <p class="form-error" data-error="photos" hidden></p>
        </section>

        <!-- 3. Details -->
        <section class="panel li-step">
          <h2 class="li-step__title"><span class="li-step__num">3</span> Item details</h2>

          <label class="bx-label" for="fTitle">Title</label>
          <input type="text" id="fTitle" class="text-input" maxlength="70" placeholder="e.g. Kalkulator Casio FX-991EX (Second)" />
          <div class="field-foot"><p class="form-error" data-error="title" hidden></p><span class="counter"><span id="titleCount">0</span>/70</span></div>

          <div class="two-col">
            <div>
              <label class="bx-label" for="fCategory">Category</label>
              <select id="fCategory" class="select">
                <option value="" selected disabled>Choose a category</option>
                <option value="education">Education</option>
                <option value="electronics">Electronics</option>
                <option value="furniture">Furniture</option>
                <option value="fashion">Fashion</option>
                <option value="hobbies">Hobbies</option>
                <option value="self-care">Self-Care</option>
                <option value="others">Others</option>
              </select>
              <p class="form-error" data-error="category" hidden></p>
            </div>
            <div>
              <span class="bx-label" id="condLabel">Condition</span>
              <div class="seg" role="radiogroup" aria-labelledby="condLabel">
                <label><input type="radio" name="condition" value="like-new" /><span>Like New</span></label>
                <label><input type="radio" name="condition" value="good" checked /><span>Good</span></label>
                <label><input type="radio" name="condition" value="fair" /><span>Fair</span></label>
              </div>
            </div>
          </div>

          <label class="bx-label" for="fDesc">Description</label>
          <textarea id="fDesc" class="text-input" rows="4" maxlength="800" placeholder="Brand, size, how long you used it, any scratches or missing parts…"></textarea>
          <div class="field-foot"><p class="form-error" data-error="desc" hidden></p><span class="counter"><span id="descCount">0</span>/800</span></div>
        </section>

        <!-- 4. Price / auction -->
        <section class="panel li-step">
          <h2 class="li-step__title"><span class="li-step__num">4</span> <span id="priceStepTitle">Price</span></h2>

          <!-- Sell -->
          <div data-for="marketplace">
            <label class="bx-label" for="fPrice">Price</label>
            <div class="prefix-input"><span>Rp</span><input type="text" id="fPrice" inputmode="numeric" placeholder="150.000" autocomplete="off" /></div>
            <p class="form-error" data-error="price" hidden></p>
            <label class="check-row"><input type="checkbox" id="fNego" /> Price is negotiable</label>
          </div>

          <!-- Auction -->
          <div data-for="auction" hidden>
            <div class="two-col">
              <div>
                <label class="bx-label" for="fStart">Starting bid</label>
                <div class="prefix-input"><span>Rp</span><input type="text" id="fStart" inputmode="numeric" placeholder="100.000" autocomplete="off" /></div>
                <p class="form-error" data-error="start" hidden></p>
              </div>
              <div>
                <label class="bx-label" for="fDuration">Auction length</label>
                <select id="fDuration" class="select">
                  <option value="1">1 day</option>
                  <option value="3" selected>3 days</option>
                  <option value="5">5 days</option>
                  <option value="7">7 days</option>
                </select>
              </div>
            </div>
            <label class="bx-label" for="fBuyNow">Buy Now price <small>(optional — ends the auction right away)</small></label>
            <div class="prefix-input"><span>Rp</span><input type="text" id="fBuyNow" inputmode="numeric" placeholder="No Buy Now" autocomplete="off" /></div>
            <p class="form-error" data-error="buynow" hidden></p>
            <p class="hint">The auction starts after an admin approves it.</p>
          </div>
        </section>

        <!-- 5. Meet-up -->
        <section class="panel li-step">
          <h2 class="li-step__title"><span class="li-step__num">5</span> Meet-up</h2>
          <label class="bx-label" for="fTikum">Suggested meet-up point (tikum)</label>
          <select id="fTikum" class="select"><option value="Biologi">Biologi</option><option value="FEB">FEB</option><option value="Farmasi">Farmasi</option><option value="Filsafat">Filsafat</option><option value="Geografi">Geografi</option><option value="FH">FH</option><option value="FIB">FIB</option><option value="FISIPOL">FISIPOL</option><option value="FK-KMK">FK-KMK</option><option value="FKG">FKG</option><option value="FKH">FKH</option><option value="FKT">FKT</option><option value="FMIPA">FMIPA</option><option value="FAPERTA">FAPERTA</option><option value="FAPET">FAPET</option><option value="Psikologi">Psikologi</option><option value="FT">FT</option><option value="FTP">FTP</option><option value="SV">SV</option><option value="SPs">SPs</option></select>
          <p class="hint">Buyers see this as your usual tikum. <a href="meetup-map.html" class="text-link">See the meet-up map</a></p>
        </section>

        <!-- Submit -->
        <section class="panel li-submit">
          <p class="moderation-note"><i data-lucide="shield-check"></i>
            <span><strong>Admin review:</strong> your listing stays hidden until an admin approves it (usually within a day). You'll get a notification.</span></p>
          <label class="check-row"><input type="checkbox" id="fAgree" /> I own this item and the photos are real.</label>
          <p class="form-error" data-error="agree" hidden></p>
          <div class="li-submit__actions">
            <button type="button" class="btn btn--outline" id="clearDraft"><span>Clear form</span></button>
            <button type="submit" class="btn btn--primary btn--keep" id="submitBtn"><i data-lucide="send"></i><span>Submit for Review</span></button>
          </div>
        </section>
      </form>

      <!-- Success -->
      <section class="panel li-done" id="doneCard" hidden tabindex="-1" aria-labelledby="doneTitle">
        <span class="li-done__icon"><i data-lucide="hourglass"></i></span>
        <h2 id="doneTitle">Submitted for review!</h2>
        <p>An admin will check <strong id="doneName"></strong>. We'll notify you when it's live.</p>
        <div class="li-done__actions">
          <a href="my-listings.html#pending" class="btn btn--primary btn--keep"><i data-lucide="package"></i><span>Go to My Listings</span></a>
          <button type="button" class="btn btn--outline" id="anotherBtn"><span>List another item</span></button>
        </div>
      </section>
    </main>

    <!-- ---------- RIGHT PANEL: live preview ---------- -->
    <aside class="rightbar" aria-label="Preview">
      <section class="panel preview">
        <h2 class="panel__title"><i data-lucide="eye" class="icon-blue"></i> Preview</h2>
        <article class="pv-card" id="pvCard" aria-label="How your listing will look">
          <div class="pv-card__media" id="pvMedia">
            <span class="pv-card__tag" id="pvTag">Marketplace</span>
            <i data-lucide="image"></i>
          </div>
          <div class="pv-card__body">
            <h3 id="pvTitle">Your item title</h3>
            <div class="pv-card__row"><strong id="pvPrice">Rp –</strong><span class="pv-cond" id="pvCond">Good</span></div>
            <span class="pv-card__meta"><i data-lucide="map-pin"></i> <span id="pvTikum"></span></span>
          </div>
        </article>
        <p class="hint">This is how students will see your card.</p>
      </section>

      <section class="panel">
        <h2 class="panel__title"><i data-lucide="lightbulb" class="icon-yellow"></i> Tips to sell faster</h2>
        <ul class="tip-list">
          <li><i data-lucide="circle-check"></i> Take photos in daylight from a few angles.</li>
          <li><i data-lucide="circle-check"></i> Be honest about scratches or missing parts.</li>
          <li><i data-lucide="circle-check"></i> Check similar items in Marketplace for a fair price.</li>
          <li><i data-lucide="circle-check"></i> Reply to chats quickly.</li>
        </ul>
      </section>
    </aside>
  </div>
@endsection
