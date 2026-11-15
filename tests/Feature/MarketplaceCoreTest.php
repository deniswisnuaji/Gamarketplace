<?php

use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use App\Models\WantedRequest;
use Database\Seeders\MarketplaceReferenceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(MarketplaceReferenceSeeder::class);
    Storage::fake('public');
    Storage::fake('local');
    $this->seller = User::factory()->create();
    $this->buyer = User::factory()->create();
    $this->admin = User::factory()->create(['role' => 'admin']);
});

function corePhoto(): string
{
    $image = UploadedFile::fake()->image('item.jpg', 20, 20);

    return 'data:image/jpeg;base64,'.base64_encode(file_get_contents($image->getPathname()));
}
function coreListing(array $overrides = []): array
{
    return array_replace(['type' => 'marketplace', 'title' => 'Working study lamp', 'category' => 'electronics', 'condition' => 'good', 'desc' => 'Fully working desk lamp for study.', 'tikum' => 'FT', 'price' => 50000, 'nego' => true, 'photos' => [corePhoto()]], $overrides);
}
function coreRequest(array $overrides = []): array
{
    return array_replace(['title' => 'Looking for a study lamp', 'category' => 'electronics', 'condition' => 'any', 'tikum' => 'FT', 'min' => 0, 'max' => 100000, 'neededBy' => now()->addDays(7)->getTimestampMs(), 'photos' => []], $overrides);
}

test('listing lifecycle persists normalized data and protects ownership and pending visibility', function () {
    $id = $this->actingAs($this->seller)->postJson('/gm/api/action/createListing', ['data' => coreListing(['sellerId' => $this->buyer->id, 'moderation' => 'approved'])])->assertOk()->assertJsonPath('result.moderation', 'pending')->json('result.id');
    $listing = Listing::findOrFail($id);
    expect($listing->seller_id)->toBe($this->seller->id)->and($listing->category->slug)->toBe('electronics')->and($listing->point->short_name)->toBe('FT');
    Storage::disk('public')->assertExists($listing->photos->first()->path);
    $this->actingAs($this->buyer)->postJson('/gm/api/action/updateListing', ['id' => $id, 'patch' => ['title' => 'Stolen title']])->assertForbidden();
    $this->postJson('/gm/api/action/addView', ['id' => $id])->assertNotFound();
    expect($this->getJson('/gm/api/state')->json('state.listings'))->toBe([]);
    $this->actingAs($this->admin)->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'rejected'])->assertUnprocessable();
    $this->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'rejected', 'reason' => 'Add item details'])->assertOk();
    $this->actingAs($this->seller)->postJson('/gm/api/action/updateListing', ['id' => $id, 'patch' => ['title' => 'Working adjustable desk lamp'], 'resubmit' => true])->assertOk()->assertJsonPath('result.moderation', 'pending');
    $this->actingAs($this->admin)->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'approved'])->assertOk();
    $this->actingAs($this->buyer)->getJson('/gm/api/state')->assertOk()->assertJsonPath('state.listings.0.id', $id);
    expect(DB::table('moderation_actions')->count())->toBe(2);
});

test('suspension hides approved listings without restoring manually hidden listings', function () {
    $one = $this->actingAs($this->seller)->postJson('/gm/api/action/createListing', ['data' => coreListing()])->assertOk()->json('result.id');
    $two = $this->postJson('/gm/api/action/createListing', ['data' => coreListing()])->assertOk()->json('result.id');
    $this->actingAs($this->admin)->postJson('/gm/api/action/moderate', ['id' => $one, 'decision' => 'approved'])->assertOk();
    $this->postJson('/gm/api/action/moderate', ['id' => $two, 'decision' => 'hidden'])->assertOk();
    $this->postJson('/gm/api/action/setUserStatus', ['id' => $this->seller->id, 'status' => 'suspended', 'reason' => 'Review'])->assertOk();
    $this->actingAs($this->buyer)->getJson('/gm/api/state')->assertJsonCount(0, 'state.listings');
    $this->actingAs($this->admin)->postJson('/gm/api/action/setUserStatus', ['id' => $this->seller->id, 'status' => 'active'])->assertOk();
    $this->actingAs($this->buyer)->getJson('/gm/api/state')->assertJsonCount(1, 'state.listings');
    expect(Listing::find($two)->moderation)->toBe('hidden');
});

test('invalid references and image payloads leave no partial listing or files', function () {
    $this->actingAs($this->seller)->postJson('/gm/api/action/createListing', ['data' => coreListing(['category' => 'missing'])])->assertUnprocessable();
    $this->postJson('/gm/api/action/createListing', ['data' => coreListing(['photos' => [corePhoto(), 'data:image/jpeg;base64,ZmFrZQ==']])])->assertUnprocessable();
    expect(Listing::count())->toBe(0)->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('requests have real foreign keys and unique owned offers', function () {
    $id = $this->actingAs($this->seller)->postJson('/gm/api/action/createRequest', ['data' => coreRequest()])->assertOk()->json('result.id');
    $offer = ['item' => 'Adjustable study lamp', 'price' => 50000, 'condition' => 'good', 'msg' => 'Can meet tomorrow'];
    $this->postJson('/gm/api/action/offerOnRequest', ['id' => $id, 'offer' => $offer])->assertUnprocessable();
    $this->actingAs($this->buyer)->postJson('/gm/api/action/offerOnRequest', ['id' => $id, 'offer' => $offer])->assertOk();
    $this->postJson('/gm/api/action/offerOnRequest', ['id' => $id, 'offer' => $offer])->assertUnprocessable();
    $this->postJson('/gm/api/action/setRequestFound', ['id' => $id, 'found' => true])->assertForbidden();
    $this->postJson('/gm/api/action/withdrawRequestOffer', ['id' => $id])->assertOk();
    $this->postJson('/gm/api/action/offerOnRequest', ['id' => $id, 'offer' => $offer])->assertOk();
    $this->actingAs($this->seller)->postJson('/gm/api/action/setRequestFound', ['id' => $id, 'found' => true])->assertOk();
    expect(WantedRequest::find($id)->category->slug)->toBe('electronics')->and(DB::table('request_offers')->count())->toBe(1);
});

test('report evidence is private and only redacted verified scams are public', function () {
    $payload = ['kind' => 'scam', 'type' => 'phishing', 'channel' => 'online', 'title' => 'Private sensitive incident', 'desc' => 'Private incident description with personal data', 'ident' => 'private@example.com', 'anonymous' => true, 'evidence' => [corePhoto()]];
    $id = $this->actingAs($this->seller)->postJson('/gm/api/action/createReport', ['data' => $payload])->assertOk()->json('result.id');
    $evidence = Report::find($id)->evidence->first();
    $this->actingAs($this->buyer)->get('/gm/api/reports/evidence/'.$evidence->id)->assertForbidden();
    $this->getJson('/gm/api/state')->assertJsonCount(0, 'state.reports');
    $this->postJson('/gm/api/action/setReportStatus', ['id' => $id, 'status' => 'verified'])->assertForbidden();
    $this->actingAs($this->admin)->get('/gm/api/reports/evidence/'.$evidence->id)->assertOk();
    $this->postJson('/gm/api/action/setReportStatus', ['id' => $id, 'status' => 'verified', 'publicTitle' => 'Phishing warning', 'publicSummary' => 'Never enter your credentials into unofficial websites.'])->assertOk();
    $public = $this->actingAs($this->buyer)->getJson('/gm/api/state')->assertOk()->json('state.reports.0');
    expect($public['reporterId'])->toBeNull()->and($public['ident'])->toBe('')->and($public['evidence'])->toBe([])->and($public['title'])->toBe('Phishing warning')->and($public['desc'])->not->toContain('personal data');
});

test('bug reports are resolved privately and never published', function () {
    $id = $this->actingAs($this->seller)->postJson('/gm/api/action/createReport', ['data' => ['kind' => 'bug', 'title' => 'Settings page has an error', 'desc' => 'When I save my profile the button is stuck.']])->assertOk()->json('result.id');
    $this->actingAs($this->admin)->postJson('/gm/api/action/setReportStatus', ['id' => $id, 'status' => 'verified'])->assertUnprocessable();
    $this->postJson('/gm/api/action/setReportStatus', ['id' => $id, 'status' => 'dismissed', 'reason' => 'Fixed the settings form.'])->assertOk();
    $this->actingAs($this->buyer)->getJson('/gm/api/state')->assertJsonCount(0, 'state.reports');
    $this->actingAs($this->seller)->getJson('/gm/api/state')->assertJsonPath('state.reports.0.reviewNote', 'Fixed the settings form.');
});

test('database itself rejects orphan categories invalid budgets and incompatible chat contexts', function () {
    $this->actingAs($this->seller)->postJson('/gm/api/action/createListing', ['data' => coreListing()])->assertOk();
    expect(fn () => DB::table('listings')->update(['category_id' => 999999]))->toThrow(QueryException::class);
    expect(fn () => DB::table('listings')->update(['price_rupiah' => -1]))->toThrow(QueryException::class);
    $this->postJson('/gm/api/action/createRequest', ['data' => coreRequest()])->assertOk();
    expect(fn () => DB::table('wanted_requests')->update(['budget_min_rupiah' => 200000]))->toThrow(QueryException::class);
});

test('malformed identifiers and data are rejected without server errors', function () {
    $this->actingAs($this->seller)->postJson('/gm/api/action/updateListing', ['id' => [1, 2], 'patch' => ['title' => 'Title']])->assertUnprocessable();
    $this->postJson('/gm/api/action/createListing', ['data' => 'invalid'])->assertUnprocessable();
    $this->postJson('/gm/api/action/createRequest', ['data' => 'invalid'])->assertUnprocessable();
    $this->postJson('/gm/api/action/createReport', ['data' => 'invalid'])->assertUnprocessable();
    $this->postJson('/gm/api/action/offerOnRequest', ['id' => 1, 'offer' => 'invalid'])->assertUnprocessable();
});

test('administrator sees the request workspace', function () {
    $this->actingAs($this->admin)->get('/gm/admin.html')->assertOk()->assertSee('Product Requests')->assertSee('requestRows', false);
});

test('seller price drops notify saved users only after approval without duplicate alerts', function () {
    $id = $this->actingAs($this->seller)->postJson('/gm/api/action/createListing', ['data' => coreListing()])->assertOk()->json('result.id');
    $this->actingAs($this->admin)->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'approved'])->assertOk();
    $this->actingAs($this->buyer)->postJson('/gm/api/action/toggleSaved', ['id' => $id, 'kind' => 'listing'])->assertOk();
    $this->actingAs($this->seller)->postJson('/gm/api/action/updateListing', ['id' => $id, 'patch' => ['price' => 30000]])->assertOk()->assertJsonPath('result.moderation', 'pending');
    expect(DB::table('notifications')->where('type', 'drop')->count())->toBe(0);
    $this->actingAs($this->admin)->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'approved'])->assertOk();
    expect(DB::table('notifications')->where('type', 'drop')->where('user_id', $this->buyer->id)->count())->toBe(1);
    $this->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'approved'])->assertOk();
    $this->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'hidden'])->assertOk();
    $this->postJson('/gm/api/action/moderate', ['id' => $id, 'decision' => 'approved'])->assertOk();
    expect(DB::table('notifications')->where('type', 'drop')->count())->toBe(1);
    $this->postJson('/gm/api/action/updateListing', ['id' => $id, 'patch' => ['price' => 20000]])->assertOk();
    expect(DB::table('notifications')->where('type', 'drop')->count())->toBe(2)
        ->and(Listing::findOrFail($id)->published_price_rupiah)->toBe(20000)
        ->and((int) DB::table('listing_wishlists')->where('listing_id', $id)->value('price_at_save_rupiah'))->toBe(50000);
});
