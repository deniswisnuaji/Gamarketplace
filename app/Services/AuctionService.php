<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuctionService
{
    public function placeBid(User $user, int $listingId, int $amount): Bid
    {
        return DB::transaction(function () use ($user, $listingId, $amount) {
            [$auction, $listing] = $this->lockOpenAuction($user, $listingId);
            $previous = $this->highestBid($auction);
            $minimum = $previous ? $previous->amount_rupiah + $this->increment($previous->amount_rupiah) : $listing->price_rupiah;

            if ($previous && (int) $previous->bidder_id === (int) $user->id) {
                throw ValidationException::withMessages(['amount' => "You're already the highest bidder."]);
            }
            if ($amount < $minimum || $amount > 1000000000000) {
                throw ValidationException::withMessages(['amount' => 'Your bid must be at least Rp '.number_format($minimum, 0, ',', '.').'.']);
            }

            $bid = Bid::create(['auction_id' => $auction->id, 'bidder_id' => $user->id, 'amount_rupiah' => $amount, 'is_buy_now' => false]);
            $this->notify($listing->seller_id, 'offer', 'New bid on “'.$listing->title.'”.', '/gm/bidding.html', ['listing_id' => $listing->id]);
            if ($previous) {
                $this->notify($previous->bidder_id, 'outbid', 'You have been outbid on “'.$listing->title.'”.', '/gm/bidding.html', ['listing_id' => $listing->id]);
            }

            return $bid;
        }, 5);
    }

    public function buyNow(User $user, int $listingId): Bid
    {
        return DB::transaction(function () use ($user, $listingId) {
            [$auction, $listing] = $this->lockOpenAuction($user, $listingId);
            $previous = $this->highestBid($auction);
            if (! $auction->buy_now_rupiah || ($previous && $previous->amount_rupiah >= $auction->buy_now_rupiah)) {
                throw ValidationException::withMessages(['id' => 'Buy Now is not available.']);
            }

            $bid = Bid::create(['auction_id' => $auction->id, 'bidder_id' => $user->id, 'amount_rupiah' => $auction->buy_now_rupiah, 'is_buy_now' => true]);
            $auction->ends_at = now();
            $auction->save();
            $this->closeLocked($auction, $listing);

            return $bid;
        }, 5);
    }

    public function finalizeDue(): int
    {
        $closed = 0;
        Auction::whereNull('finalized_at')->whereNotNull('ends_at')->where('ends_at', '<=', now())
            ->select('id')->chunkById(100, function ($auctions) use (&$closed) {
                foreach ($auctions as $auction) {
                    $closed += (int) $this->finalize($auction->id);
                }
            });

        return $closed;
    }

    public function finalize(int $auctionId): bool
    {
        return DB::transaction(function () use ($auctionId) {
            $auction = Auction::whereKey($auctionId)->lockForUpdate()->firstOrFail();
            if ($auction->finalized_at || ! $auction->ends_at || $auction->ends_at->isFuture()) {
                return false;
            }
            $listing = Listing::withTrashed()->whereKey($auction->listing_id)->lockForUpdate()->firstOrFail();
            $this->closeLocked($auction, $listing);

            return true;
        }, 5);
    }

    /** @return array{Auction, Listing} */
    private function lockOpenAuction(User $user, int $listingId): array
    {
        $auction = Auction::where('listing_id', $listingId)->lockForUpdate()->firstOrFail();
        $listing = Listing::whereKey($listingId)->lockForUpdate()->firstOrFail();
        abort_unless(User::whereKey($user->id)->where('status', 'active')->exists(), 403);
        if ($listing->type !== 'auction' || $listing->moderation !== 'approved' || $listing->availability !== 'available'
            || ! User::whereKey($listing->seller_id)->where('status', 'active')->exists()
            || $auction->finalized_at || ! $auction->ends_at || ! $auction->ends_at->isFuture()) {
            throw ValidationException::withMessages(['id' => 'This auction is not open for bidding.']);
        }
        if ((int) $listing->seller_id === (int) $user->id) {
            throw ValidationException::withMessages(['id' => 'You cannot bid on your own auction.']);
        }

        return [$auction, $listing];
    }

    private function highestBid(Auction $auction): ?Bid
    {
        return Bid::where('auction_id', $auction->id)->orderByDesc('amount_rupiah')->orderByDesc('id')->first();
    }

    private function increment(int $amount): int
    {
        return $amount < 100000 ? 5000 : ($amount < 1000000 ? 25000 : 50000);
    }

    private function closeLocked(Auction $auction, Listing $listing): void
    {
        if ($auction->finalized_at) {
            return;
        }
        $winner = $this->highestBid($auction);
        $auction->winner_bid_id = $winner?->id;
        $auction->finalized_at = now();
        $auction->save();
        if ($winner) {
            $listing->availability = 'sold';
            $listing->save();
            $this->notify($winner->bidder_id, 'won', 'You won “'.$listing->title.'”. Chat with the seller to arrange a meet-up.', '/gm/messages.html?to='.$listing->seller_id.'&listing='.$listing->id, ['listing_id' => $listing->id]);
            $this->notify($listing->seller_id, 'won', 'Your auction “'.$listing->title.'” ended with a winning bid.', '/gm/messages.html?to='.$winner->bidder_id.'&listing='.$listing->id, ['listing_id' => $listing->id]);
        } else {
            $this->notify($listing->seller_id, 'info', 'Your auction “'.$listing->title.'” ended with no bids.', '/gm/my-listings.html', ['listing_id' => $listing->id]);
        }
    }

    /** @param array<string, int> $targets */
    public function notify(User|int $user, string $type, string $text, string $link, array $targets = []): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $preference = ['outbid' => 'notif_outbid', 'offer' => 'notif_offers', 'message' => 'notif_messages', 'scam' => 'notif_scam', 'drop' => 'notif_price_drop'][$type] ?? null;
        if ($preference && DB::table('user_preferences')->where('user_id', $userId)->where($preference, false)->exists()) {
            return;
        }
        DB::table('notifications')->insert(array_merge([
            'user_id' => $userId, 'type' => $type, 'text' => $text, 'link' => $link,
            'created_at' => now(), 'updated_at' => now(),
        ], array_intersect_key($targets, array_flip(['listing_id', 'request_id', 'report_id', 'conversation_id']))));
    }
}
