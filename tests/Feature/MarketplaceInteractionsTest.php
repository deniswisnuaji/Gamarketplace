<?php

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Listing;
use App\Models\MeetupPoint;
use App\Models\User;
use App\Services\AuctionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function interactionFixture(int $price = 50000): array
{
    $seller = User::factory()->create()->fresh();
    $buyer = User::factory()->create()->fresh();
    $other = User::factory()->create()->fresh();
    $category = Category::create(['slug' => 'electronics', 'name' => 'Electronics', 'active' => true]);
    $point = MeetupPoint::create(['slug' => 'library', 'short_name' => 'Library', 'name' => 'Campus Library', 'kind' => 'public', 'active' => true]);
    $listing = Listing::create([
        'seller_id' => $seller->id, 'category_id' => $category->id, 'meetup_point_id' => $point->id,
        'type' => 'auction', 'title' => 'A functional calculator', 'condition' => 'good',
        'description' => 'A working calculator for a campus student.', 'price_rupiah' => $price,
        'moderation' => 'approved', 'availability' => 'available', 'approved_at' => now(),
    ]);
    $auction = Auction::create(['listing_id' => $listing->id, 'duration_days' => 3, 'buy_now_rupiah' => $price + 200000, 'ends_at' => now()->addDays(3)]);

    return compact('seller', 'buyer', 'other', 'category', 'point', 'listing', 'auction');
}

test('bidding rejects owners current leaders and stale minimums', function () {
    extract(interactionFixture());
    $this->actingAs($seller)->postJson('/gm/api/action/placeBid', ['id' => $listing->id, 'amount' => 50000])->assertUnprocessable();
    $this->actingAs($buyer)->postJson('/gm/api/action/placeBid', ['id' => $listing->id, 'amount' => 50000])->assertOk()->assertJsonPath('result.ok', true);
    $this->postJson('/gm/api/action/placeBid', ['id' => $listing->id, 'amount' => 55000])->assertUnprocessable();
    $this->actingAs($other)->postJson('/gm/api/action/placeBid', ['id' => $listing->id, 'amount' => 54999])->assertUnprocessable();
    $this->postJson('/gm/api/action/placeBid', ['id' => $listing->id, 'amount' => 55000])->assertOk();
    expect(Bid::count())->toBe(2);
});

test('bid increments change at both rupiah boundaries', function (int $start, int $increment) {
    extract(interactionFixture($start));
    $service = app(AuctionService::class);
    $service->placeBid($buyer, $listing->id, $start);
    expect(fn () => $service->placeBid($other, $listing->id, $start + $increment - 1))->toThrow(ValidationException::class);
    $service->placeBid($other, $listing->id, $start + $increment);
    expect(Bid::count())->toBe(2);
})->with([[95000, 5000], [100000, 25000], [1000000, 50000]]);

test('buy now closes once and cannot overwrite the winner', function () {
    extract(interactionFixture());
    $this->actingAs($buyer)->postJson('/gm/api/action/buyNow', ['id' => $listing->id])->assertOk();
    $winnerId = $auction->fresh()->winner_bid_id;
    $this->actingAs($other)->postJson('/gm/api/action/buyNow', ['id' => $listing->id])->assertUnprocessable();
    expect($listing->fresh()->availability)->toBe('sold')
        ->and($auction->fresh()->winner_bid_id)->toBe($winnerId)
        ->and(Bid::count())->toBe(1)
        ->and(app(AuctionService::class)->finalizeDue())->toBe(0)
        ->and(DB::table('notifications')->where('type', 'won')->count())->toBe(2);
});

test('finalizing an expired auction is idempotent and refuses later bids', function () {
    extract(interactionFixture());
    $service = app(AuctionService::class);
    $bid = $service->placeBid($buyer, $listing->id, 50000);
    $auction->update(['ends_at' => now()->subSecond()]);
    $this->actingAs($other)->postJson('/gm/api/action/placeBid', ['id' => $listing->id, 'amount' => 55000])->assertUnprocessable();
    expect($service->finalizeDue())->toBe(1)
        ->and($service->finalizeDue())->toBe(0)
        ->and($auction->fresh()->winner_bid_id)->toBe($bid->id)
        ->and(DB::table('notifications')->where('type', 'won')->count())->toBe(2);
});

test('hidden auctions and suspended sellers cannot accept bids or buy now', function () {
    extract(interactionFixture());
    $listing->update(['moderation' => 'hidden']);
    $this->actingAs($buyer)->postJson('/gm/api/action/buyNow', ['id' => $listing->id])->assertUnprocessable();
    $listing->update(['moderation' => 'approved']);
    $seller->forceFill(['status' => 'suspended'])->save();
    $this->postJson('/gm/api/action/placeBid', ['id' => $listing->id, 'amount' => 50000])->assertUnprocessable();
    $this->postJson('/gm/api/action/buyNow', ['id' => $listing->id])->assertUnprocessable();
    expect(Bid::count())->toBe(0);
});

test('conversations reuse exact context and reject nonmember access', function () {
    extract(interactionFixture());
    $payload = ['otherId' => $seller->id, 'ref' => ['kind' => 'listing', 'id' => $listing->id]];
    $context = $this->actingAs($buyer)->postJson('/gm/api/action/openConversation', $payload)->assertOk()->json('result.id');
    $repeat = $this->postJson('/gm/api/action/openConversation', $payload)->assertOk()->json('result.id');
    $direct = $this->postJson('/gm/api/action/openConversation', ['otherId' => $seller->id])->assertOk()->json('result.id');
    expect($repeat)->toBe($context)->and($direct)->not->toBe($context)
        ->and(DB::table('conversation_members')->count())->toBe(4);
    $this->postJson('/gm/api/action/sendMessage', ['convId' => $context, 'msg' => ['text' => 'Still available?']])->assertOk();
    $this->actingAs($other)->postJson('/gm/api/action/sendMessage', ['convId' => $context, 'msg' => ['text' => 'Unauthorized']])->assertForbidden();
    $this->postJson('/gm/api/action/markConversationRead', ['convId' => $context])->assertForbidden();
    $this->postJson('/gm/api/action/openConversation', ['otherId' => $buyer->id, 'ref' => ['kind' => 'listing', 'id' => $listing->id]])->assertForbidden();
    expect(DB::table('messages')->count())->toBe(1);
});

test('only recipient can answer a pending meet-up once', function () {
    extract(interactionFixture());
    $conversationId = $this->actingAs($buyer)->postJson('/gm/api/action/openConversation', ['otherId' => $seller->id])->assertOk()->json('result.id');
    $messageId = $this->postJson('/gm/api/action/sendMessage', [
        'convId' => $conversationId,
        'msg' => ['type' => 'meetup', 'place' => $point->short_name, 'date' => now('Asia/Jakarta')->addDay()->format('Y-m-d'), 'time' => '13:00', 'status' => 'accepted', 'from' => $seller->id],
    ])->assertOk()->json('result.id');
    $this->assertDatabaseHas('meetup_proposals', ['message_id' => $messageId, 'status' => 'pending']);
    $this->assertDatabaseHas('messages', ['id' => $messageId, 'sender_id' => $buyer->id]);
    $payload = ['convId' => $conversationId, 'msgId' => $messageId, 'accept' => true];
    $this->postJson('/gm/api/action/respondMeetup', $payload)->assertForbidden();
    $this->actingAs($seller)->postJson('/gm/api/action/respondMeetup', $payload)->assertOk();
    $this->postJson('/gm/api/action/respondMeetup', $payload)->assertUnprocessable();
    $this->assertDatabaseHas('meetup_proposals', ['message_id' => $messageId, 'status' => 'accepted', 'responded_by' => $seller->id]);
});

test('chat images stay private and reads are recorded only for members', function () {
    Storage::fake('local');
    extract(interactionFixture());
    $conversationId = $this->actingAs($buyer)->postJson('/gm/api/action/openConversation', ['otherId' => $seller->id])->assertOk()->json('result.id');
    $image = UploadedFile::fake()->image('photo.jpg', 10, 10);
    $messageId = $this->postJson('/gm/api/action/sendMessage', ['convId' => $conversationId, 'msg' => ['type' => 'image', 'src' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents($image->getPathname()))]])->assertOk()->json('result.id');
    $path = DB::table('messages')->where('id', $messageId)->value('image_path');
    Storage::disk('local')->assertExists($path);
    $this->actingAs($other)->get('/gm/api/messages/'.$messageId.'/image')->assertForbidden();
    $this->actingAs($seller)->get('/gm/api/messages/'.$messageId.'/image')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->postJson('/gm/api/action/markConversationRead', ['convId' => $conversationId])->assertOk();
    $this->postJson('/gm/api/action/markConversationRead', ['convId' => $conversationId])->assertOk();
    expect(DB::table('message_reads')->where('message_id', $messageId)->count())->toBe(2);
});

test('wishlist and notifications cannot mutate another account and preferences are respected', function () {
    extract(interactionFixture());
    $payload = ['userId' => $buyer->id, 'kind' => 'listing', 'id' => $listing->id];
    $this->actingAs($buyer)->postJson('/gm/api/action/toggleSaved', $payload)->assertOk()->assertJsonPath('result', true);
    $this->postJson('/gm/api/action/toggleSaved', $payload)->assertOk()->assertJsonPath('result', false);
    $this->actingAs($other)->postJson('/gm/api/action/toggleSaved', $payload)->assertForbidden();
    DB::table('user_preferences')->insert(['user_id' => $seller->id, 'notif_messages' => false, 'created_at' => now(), 'updated_at' => now()]);
    app(AuctionService::class)->notify($seller->id, 'message', 'Muted message', '/gm/messages.html');
    expect(DB::table('notifications')->count())->toBe(0);
    app(AuctionService::class)->notify($buyer->id, 'info', 'Private notice', '/gm/notifications.html');
    $id = DB::table('notifications')->value('id');
    $this->postJson('/gm/api/action/markNotification', ['id' => $id])->assertNotFound();
    $this->postJson('/gm/api/action/deleteNotification', ['id' => $id])->assertNotFound();
    $this->postJson('/gm/api/action/markAllNotifications', ['userId' => $buyer->id])->assertForbidden();
    $this->assertDatabaseHas('notifications', ['id' => $id, 'read_at' => null]);
    $this->actingAs($buyer)->postJson('/gm/api/action/markNotification', ['id' => $id])->assertOk();
    expect(DB::table('notifications')->value('read_at'))->not->toBeNull();
});
