<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Models\MarketplaceNotification;
use App\Models\ModerationAction;
use App\Models\Report;
use App\Models\ReportEvidence;
use App\Models\RequestOffer;
use App\Models\User;
use App\Models\WantedRequest;
use App\Services\MarketplaceData;
use App\Services\MarketplaceFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoreController extends Controller
{
    private function recordId(Request $request): int
    {
        $request->validate(['id' => 'required|integer|min:1']);

        return $request->integer('id');
    }

    private function viewer(Request $request): User
    {
        $u = $request->user();
        abort_unless($u && $u->status === 'active', 403);

        return $u;
    }

    private function admin(Request $request): User
    {
        $u = $this->viewer($request);
        abort_unless($u->role === 'admin', 403);

        return $u;
    }

    private function owner(Request $request, int $owner): User
    {
        $u = $this->viewer($request);
        abort_unless($u->id === $owner || $u->role === 'admin', 403);

        return $u;
    }

    private function respond(Request $request, mixed $result = null): JsonResponse
    {
        return response()->json(['result' => $result ?? ['ok' => true], 'state' => app(MarketplaceData::class)->snapshot($request->user()?->fresh())]);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function publishPrice(Listing $listing): void
    {
        if ($listing->type !== 'marketplace' || $listing->moderation !== 'approved') {
            return;
        }
        if ($listing->published_price_rupiah !== null && $listing->price_rupiah < $listing->published_price_rupiah) {
            foreach (DB::table('listing_wishlists')->where('listing_id', $listing->id)->pluck('user_id') as $id) {
                $this->notify($id, 'drop', 'Price dropped: '.$listing->title, 'item.html?id='.$listing->id, ['listing_id' => $listing->id]);
            }
        }
        $listing->published_price_rupiah = $listing->price_rupiah;
        $listing->save();
    }

    /** @param array<string, int> $targets */
    private function notify(int $userId, string $type, string $text, string $link, array $targets = []): void
    {
        $pref = ['offer' => 'notif_offers', 'scam' => 'notif_scam', 'drop' => 'notif_price_drop'][$type] ?? null;
        if ($pref && DB::table('user_preferences')->where('user_id', $userId)->where($pref, false)->exists()) {
            return;
        }
        MarketplaceNotification::create(['user_id' => $userId, 'type' => $type, 'text' => $text, 'link' => $link] + $targets);
    }

    /** @param array<string, int> $targets */
    private function notifyAdmins(string $type, string $text, string $link, array $targets = []): void
    {
        foreach (User::where('role', 'admin')->where('status', 'active')->pluck('id') as $id) {
            $this->notify($id, $type, $text, $link, $targets);
        }
    }

    /** @param array<string, int> $targets */
    private function audit(User $admin, string $kind, string $text, array $targets = []): void
    {
        AdminActivity::create(['admin_id' => $admin->id, 'kind' => $kind, 'text' => $text] + $targets);
    }

    private function reference(string $table, string $column, string $value): int
    {
        return (int) DB::table($table)->where($column, $value)->where('active', true)->value('id');
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed> */
    private function listingData(array $input): array
    {
        $v = Validator::make($input, [
            'type' => ['required', Rule::in(['marketplace', 'auction'])], 'title' => 'required|string|min:5|max:80', 'category' => ['required', Rule::exists('categories', 'slug')->where('active', true)], 'condition' => ['required', Rule::in(['like-new', 'good', 'fair'])], 'desc' => 'required|string|min:10|max:10000', 'tikum' => ['required', Rule::exists('meetup_points', 'short_name')->where('active', true)], 'price' => 'required|integer|min:1|max:1000000000000', 'nego' => 'sometimes|boolean', 'photos' => 'required|array|min:1|max:4', 'photos.*' => 'required|string|max:7000000', 'auction' => 'required_if:type,auction|nullable|array', 'auction.durationDays' => 'required_if:type,auction|integer|in:1,3,5,7', 'auction.buyNow' => 'nullable|integer|gt:price|max:1000000000000',
        ])->validate();

        return ['type' => $v['type'], 'title' => $v['title'], 'category_id' => $this->reference('categories', 'slug', $v['category']), 'condition' => $v['condition'], 'description' => $v['desc'], 'meetup_point_id' => $this->reference('meetup_points', 'short_name', $v['tikum']), 'price_rupiah' => $v['price'], 'negotiable' => $v['nego'] ?? false];
    }

    public function createListing(Request $request): JsonResponse
    {
        $request->validate(['data' => 'required|array']);
        $u = $this->viewer($request);
        $input = $request->input('data', []);
        $data = $this->listingData($input);
        [$paths,$created] = app(MarketplaceFiles::class)->prepare($input['photos'], 'listings');
        try {
            $l = DB::transaction(function () use ($u, $data, $input, $paths) {
                $l = Listing::create($data + ['seller_id' => $u->id]);
                foreach ($paths as $i => $path) {
                    $l->photos()->create(['path' => $path, 'position' => $i]);
                }
                if ($l->type === 'auction') {
                    $l->auction()->create(['duration_days' => $input['auction']['durationDays'], 'buy_now_rupiah' => $input['auction']['buyNow'] ?? null]);
                }
                $this->notifyAdmins('review', 'New listing to review: '.$l->title, 'admin.html#review', ['listing_id' => $l->id]);

                return $l;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($created);
            throw $e;
        }

        return $this->respond($request, app(MarketplaceData::class)->listing($l->refresh()));
    }

    public function updateListing(Request $request): JsonResponse
    {
        $l = Listing::findOrFail($this->recordId($request));
        $u = $this->owner($request, $l->seller_id);
        $patch = $request->input('patch', []);
        if (! is_array($patch)) {
            $this->fail('patch', 'Invalid update.');
        }
        $content = array_intersect_key($patch, array_flip(['title', 'category', 'condition', 'desc', 'tikum', 'price', 'nego', 'photos', 'type', 'auction']));
        if (! $content) {
            $v = Validator::make($patch, ['availability' => ['required', Rule::in(['available', 'reserved', 'sold'])]])->validate();
            if ($l->type === 'auction') {
                $this->fail('availability', 'Auction availability is managed by its closing result.');
            }
            DB::transaction(function () use ($l, $v, $u) {
                $l->update($v);
                if ($u->role === 'admin') {
                    $this->audit($u, 'edit', 'Updated availability: '.$l->title, ['listing_id' => $l->id]);
                }
            });

            return $this->respond($request, app(MarketplaceData::class)->listing($l->refresh()));
        }
        $current = app(MarketplaceData::class)->listing($l);
        $input = array_replace($current, $content);
        if ($input['type'] !== $l->type) {
            $this->fail('type', 'Listing type cannot be changed after creation.');
        }
        $data = $this->listingData($input);
        [$paths,$created] = app(MarketplaceFiles::class)->prepare($input['photos'], 'listings', array_values($l->photos->map(fn (ListingPhoto $photo): string => $photo->path)->all()));
        $old = array_values($l->photos->map(fn (ListingPhoto $photo): string => $photo->path)->all());
        try {
            DB::transaction(function () use ($l, $u, $input, $data, $paths) {
                $locked = Listing::lockForUpdate()->findOrFail($l->id);
                $a = $locked->auction()->lockForUpdate()->first();
                if ($a && ($a->ends_at || $a->bids()->exists())) {
                    $this->fail('auction', 'An approved auction cannot be edited. Create a new listing after it closes.');
                }
                $locked->fill($data);
                if ($u->role !== 'admin') {
                    $locked->moderation = 'pending';
                    $locked->moderation_reason = null;
                }
                $locked->save();
                if ($a) {
                    $a->update(['duration_days' => $input['auction']['durationDays'], 'buy_now_rupiah' => $input['auction']['buyNow'] ?? null]);
                }
                $locked->photos()->delete();
                foreach ($paths as $i => $path) {
                    $locked->photos()->create(['path' => $path, 'position' => $i]);
                }
                if ($u->role === 'admin') {
                    $this->audit($u, 'edit', 'Edited listing: '.$locked->title, ['listing_id' => $locked->id]);
                    $this->notify($locked->seller_id, 'info', 'An admin edited your listing: '.$locked->title, 'my-listings.html', ['listing_id' => $locked->id]);
                } else {
                    $this->notifyAdmins('review', 'Listing updated: '.$locked->title, 'admin.html#review', ['listing_id' => $locked->id]);
                }
                $this->publishPrice($locked);
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($created);
            throw $e;
        }
        Storage::disk('public')->delete(array_values(array_diff($old, $paths)));

        return $this->respond($request, app(MarketplaceData::class)->listing($l->refresh()));
    }

    public function deleteListing(Request $request): JsonResponse
    {
        $l = Listing::findOrFail($this->recordId($request));
        $u = $this->owner($request, $l->seller_id);
        DB::transaction(function () use ($l, $u, $request) {
            $l = Listing::lockForUpdate()->findOrFail($l->id);
            $a = $l->auction()->lockForUpdate()->first();
            if ($a && $a->bids()->exists() && ! $a->finalized_at) {
                $this->fail('listing', 'An auction with bids must finish before deletion.');
            }
            $l->delete();
            if ($u->role === 'admin') {
                $this->audit($u, 'delete', 'Removed listing: '.$l->title.' — '.$request->input('reason', ''), ['listing_id' => $l->id]);
                $this->notify($l->seller_id, 'rejected', 'Listing removed: '.$l->title, 'my-listings.html', ['listing_id' => $l->id]);
            }
        }, 3);

        return $this->respond($request);
    }

    public function moderate(Request $request): JsonResponse
    {
        $u = $this->admin($request);
        $v = $request->validate(['id' => 'required|integer', 'decision' => ['required', Rule::in(['approved', 'rejected', 'hidden', 'pending'])], 'reason' => 'required_if:decision,rejected|nullable|string|max:2000']);
        DB::transaction(function () use ($u, $v) {
            $l = Listing::lockForUpdate()->where('id', $v['id'])->firstOrFail();
            $a = $l->auction()->lockForUpdate()->first();
            $from = $l->moderation;
            if ($from === $v['decision']) {
                return;
            }
            if ($v['decision'] === 'approved' && $l->seller->status !== 'active') {
                $this->fail('listing', 'A suspended seller cannot publish listings.');
            }
            $l->moderation = $v['decision'];
            $l->moderation_reason = $v['reason'] ?? null;
            if ($v['decision'] === 'approved') {
                $l->approved_at ??= now();
                if ($a && ! $a->ends_at) {
                    $a->update(['ends_at' => now()->addDays($a->duration_days)]);
                }
            }
            $l->save();
            $this->publishPrice($l);
            ModerationAction::create(['listing_id' => $l->id, 'admin_id' => $u->id, 'from_status' => $from, 'to_status' => $l->moderation, 'reason' => $v['reason'] ?? null]);
            $this->audit($u, $l->moderation === 'approved' ? 'approve' : ($l->moderation === 'rejected' ? 'reject' : 'edit'), $l->moderation.': '.$l->title, ['listing_id' => $l->id]);
            $this->notify($l->seller_id, $l->moderation === 'approved' ? 'approved' : 'info', 'Listing '.$l->moderation.': '.$l->title.($l->moderation_reason ? ' — '.$l->moderation_reason : ''), 'my-listings.html', ['listing_id' => $l->id]);
        }, 3);

        return $this->respond($request);
    }

    public function addView(Request $request): JsonResponse
    {
        $l = Listing::visibleTo($request->user())->findOrFail($this->recordId($request));
        $l->increment('views');

        return $this->respond($request);
    }

    public function createRequest(Request $request): JsonResponse
    {
        $request->validate(['data' => 'required|array']);
        $u = $this->viewer($request);
        $input = $request->input('data', []);
        $v = Validator::make($input, ['title' => 'required|string|min:5|max:80', 'category' => ['required', Rule::exists('categories', 'slug')->where('active', true)], 'tikum' => ['required', Rule::exists('meetup_points', 'short_name')->where('active', true)], 'min' => 'required|integer|min:0|max:1000000000000', 'max' => 'required|integer|min:1|gte:min|max:1000000000000', 'condition' => ['required', Rule::in(['any', 'like-new', 'good'])], 'neededBy' => 'required|integer|min:1', 'desc' => 'nullable|string|max:5000', 'photos' => 'sometimes|array|max:3', 'photos.*' => 'string|max:7000000'])->validate();
        $needed = Carbon::createFromTimestampMs($v['neededBy']);
        if ($needed->isPast() || $needed->gt(now()->addDays(366))) {
            $this->fail('neededBy', 'Choose a date in the next year.');
        }
        [$paths,$created] = app(MarketplaceFiles::class)->prepare($v['photos'] ?? [], 'requests');
        try {
            $r = DB::transaction(function () use ($u, $v, $needed, $paths) {
                $r = WantedRequest::create(['user_id' => $u->id, 'category_id' => $this->reference('categories', 'slug', $v['category']), 'meetup_point_id' => $this->reference('meetup_points', 'short_name', $v['tikum']), 'title' => $v['title'], 'budget_min_rupiah' => $v['min'], 'budget_max_rupiah' => $v['max'], 'condition' => $v['condition'], 'needed_by' => $needed, 'description' => $v['desc'] ?? null]);
                foreach ($paths as $i => $path) {
                    $r->photos()->create(['path' => $path, 'position' => $i]);
                }

                return $r;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($created);
            throw $e;
        }

        return $this->respond($request, app(MarketplaceData::class)->wanted($r->refresh(), $u));
    }

    public function offerOnRequest(Request $request): JsonResponse
    {
        $u = $this->viewer($request);
        $request->validate(['offer' => 'required|array']);
        $v = Validator::make($request->input('offer', []), ['item' => 'required|string|min:3|max:160', 'price' => 'required|integer|min:1|max:1000000000000', 'condition' => ['required', Rule::in(['like-new', 'good', 'fair'])], 'msg' => 'nullable|string|max:2000'])->validate();
        DB::transaction(function () use ($request, $u, $v) {
            $r = WantedRequest::lockForUpdate()->findOrFail($this->recordId($request));
            abort_unless(! $r->removed && $r->user->status === 'active', 404);
            if ($r->user_id === $u->id || $r->found || $r->needed_by->isPast()) {
                $this->fail('request', 'This request is not open for your offer.');
            }
            if ($r->offers()->where('user_id', $u->id)->exists()) {
                $this->fail('request', 'You have already offered. Withdraw it before submitting again.');
            }
            $r->offers()->create(['user_id' => $u->id, 'item' => $v['item'], 'price_rupiah' => $v['price'], 'condition' => $v['condition'], 'message' => $v['msg'] ?? null]);
            $this->notify($r->user_id, 'offer', 'New offer for '.$r->title, 'looking-for.html#mine', ['request_id' => $r->id]);
        }, 3);

        return $this->respond($request);
    }

    public function withdrawRequestOffer(Request $request): JsonResponse
    {
        $u = $this->viewer($request);
        RequestOffer::where('request_id', $request->input('id'))->where('user_id', $u->id)->delete();

        return $this->respond($request);
    }

    public function setRequestFound(Request $request): JsonResponse
    {
        $r = WantedRequest::findOrFail($this->recordId($request));
        $this->owner($request, $r->user_id);
        $request->validate(['found' => 'required|boolean']);
        $r->update(['found' => $request->boolean('found')]);

        return $this->respond($request);
    }

    public function deleteRequest(Request $request): JsonResponse
    {
        $r = WantedRequest::findOrFail($this->recordId($request));
        $u = $this->owner($request, $r->user_id);
        DB::transaction(function () use ($r, $u, $request) {
            $r->update(['removed' => true]);
            $r->delete();
            if ($u->role === 'admin') {
                $this->audit($u, 'delete', 'Removed request: '.$r->title.' — '.$request->input('reason', ''));
            }
        });

        return $this->respond($request);
    }

    public function createReport(Request $request): JsonResponse
    {
        $request->validate(['data' => 'required|array']);
        $u = $this->viewer($request);
        $input = $request->input('data', []);
        $input['kind'] ??= 'scam';
        $input['type'] ??= 'other';
        $input['channel'] ??= 'online';
        $v = Validator::make($input, ['kind' => ['required', Rule::in(['scam', 'bug', 'listing', 'user', 'other'])], 'type' => ['required', Rule::in(['payment', 'noshow', 'phishing', 'fake-item', 'impersonation', 'deposit', 'other'])], 'channel' => ['required', Rule::in(['online', 'meetup'])], 'title' => 'required|string|min:5|max:80', 'desc' => 'required|string|min:10|max:5000', 'place' => 'nullable|string|max:100', 'date' => 'nullable|date|before_or_equal:today', 'ident' => 'nullable|string|max:255', 'anonymous' => 'sometimes|boolean', 'evidence' => 'sometimes|array|max:3', 'evidence.*' => 'string|max:7000000', 'listingId' => 'nullable|integer|exists:listings,id', 'subjectUserId' => 'nullable|integer|exists:users,id'])->validate();
        if (! empty($v['listingId'])) {
            Listing::visibleTo($u)->where('id', $v['listingId'])->firstOrFail();
        }
        [$paths,$created] = app(MarketplaceFiles::class)->prepare($v['evidence'] ?? [], 'report-evidence', [], true);
        try {
            $r = DB::transaction(function () use ($u, $v, $paths) {
                $r = Report::create(['reporter_id' => $u->id, 'kind' => $v['kind'], 'type' => $v['type'], 'channel' => $v['channel'], 'title' => $v['title'], 'description' => $v['desc'], 'place' => $v['place'] ?? null, 'incident_date' => $v['date'] ?? null, 'identifier' => $v['ident'] ?? null, 'anonymous' => $v['anonymous'] ?? true, 'listing_id' => $v['listingId'] ?? null, 'subject_user_id' => $v['subjectUserId'] ?? null]);
                foreach ($paths as $i => $path) {
                    $r->evidence()->create(['private_path' => $path, 'position' => $i]);
                }
                $this->notifyAdmins('scam', 'New '.$r->kind.' report: '.$r->title, 'admin.html#reports', ['report_id' => $r->id]);

                return $r;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($created);
            throw $e;
        }

        return $this->respond($request, app(MarketplaceData::class)->report($r->refresh(), $u));
    }

    public function toggleSeen(Request $request): JsonResponse
    {
        $u = $this->viewer($request);
        $on = DB::transaction(function () use ($u, $request) {
            $r = Report::where('kind', 'scam')->where('status', 'verified')->lockForUpdate()->findOrFail($this->recordId($request));
            $q = DB::table('report_seen')->where('report_id', $r->id)->where('user_id', $u->id);
            if ($q->exists()) {
                $q->delete();

                return false;
            } DB::table('report_seen')->insert(['report_id' => $r->id, 'user_id' => $u->id, 'seen_at' => now()]);

            return true;
        }, 3);

        return $this->respond($request, $on);
    }

    public function setReportStatus(Request $request): JsonResponse
    {
        $u = $this->admin($request);
        $v = $request->validate(['id' => 'required|integer', 'status' => ['required', Rule::in(['review', 'verified', 'dismissed'])], 'reason' => 'nullable|string|max:3000', 'publicTitle' => 'nullable|string|max:80', 'publicSummary' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($u, $v) {
            $r = Report::lockForUpdate()->where('id', $v['id'])->firstOrFail();
            if ($v['status'] === 'verified' && $r->kind !== 'scam') {
                $this->fail('status', 'Only scam alerts can be published. Resolve other reports privately.');
            }
            $wasVerified = $r->status === 'verified';
            $r->fill(['status' => $v['status'], 'reviewed_by' => $u->id, 'review_note' => $v['reason'] ?? null, 'verified_at' => $v['status'] === 'verified' ? ($r->verified_at ?? now()) : null]);
            if ($v['status'] === 'verified') {
                $r->public_title = $v['publicTitle'] ?? 'Verified marketplace safety warning';
                $r->public_summary = $v['publicSummary'] ?? 'The marketplace team reviewed a report of suspicious trading activity. Inspect items at a public campus meeting point, verify payments yourself, and never share passwords or OTP codes.';
            }
            $r->save();
            $this->audit($u, $r->status === 'verified' ? 'verify' : 'info', 'Report '.$r->status.': '.$r->title, ['report_id' => $r->id]);
            if (! $wasVerified && $r->status === 'verified' && $r->risk === 'high') {
                foreach (User::where('role', 'student')->where('status', 'active')->where('id', '!=', $r->reporter_id)->pluck('id') as $recipient) {
                    $this->notify($recipient, 'scam', $r->public_title, 'scam-watch.html#alert-'.$r->id, ['report_id' => $r->id]);
                }
            }
            $this->notify($r->reporter_id, 'info', 'Your '.$r->kind.' report was reviewed: '.$r->status, 'scam-watch.html#mine', ['report_id' => $r->id]);
        }, 3);

        return $this->respond($request);
    }

    public function setReportRisk(Request $request): JsonResponse
    {
        $u = $this->admin($request);
        $v = $request->validate(['id' => 'required|integer', 'risk' => ['required', Rule::in(['low', 'medium', 'high'])]]);
        DB::transaction(function () use ($v, $u) {
            $r = Report::where('id', $v['id'])->firstOrFail();
            $r->update(['risk' => $v['risk']]);
            $this->audit($u, 'edit', 'Report risk '.$r->risk.': '.$r->title, ['report_id' => $r->id]);
        });

        return $this->respond($request);
    }

    public function setUserStatus(Request $request): JsonResponse
    {
        $u = $this->admin($request);
        $v = $request->validate(['id' => 'required|integer', 'status' => ['required', Rule::in(['active', 'suspended'])], 'reason' => 'required_if:status,suspended|nullable|string|max:2000']);
        DB::transaction(function () use ($u, $v) {
            $target = User::lockForUpdate()->where('id', $v['id'])->firstOrFail();
            if ($target->role === 'admin') {
                $this->fail('user', 'Admin accounts cannot be suspended through the marketplace panel.');
            } $target->status = $v['status'];
            $target->save();
            if ($target->status === 'suspended') {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            } $this->audit($u, $target->status === 'suspended' ? 'suspend' : 'info', $target->status.': '.$target->name.' — '.($v['reason'] ?? ''), ['target_user_id' => $target->id]);
        });

        return $this->respond($request);
    }

    public function evidence(Request $request, ReportEvidence $evidence): StreamedResponse
    {
        $u = $this->viewer($request);
        abort_unless($u->role === 'admin' || $evidence->report->reporter_id === $u->id, 403);

        return Storage::disk('local')->response($evidence->private_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
