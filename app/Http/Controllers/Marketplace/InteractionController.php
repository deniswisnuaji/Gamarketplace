<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\MeetupPoint;
use App\Models\MeetupProposal;
use App\Models\Message;
use App\Models\User;
use App\Models\WantedRequest;
use App\Services\AuctionService;
use App\Services\MarketplaceData;
use App\Services\MarketplaceFiles;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class InteractionController extends Controller
{
    public function __construct(private AuctionService $auctions, private MarketplaceData $data, private MarketplaceFiles $files) {}

    public function placeBid(Request $request): JsonResponse
    {
        $input = $request->validate(['id' => 'required|integer|min:1', 'amount' => 'required|integer|min:1000|max:1000000000000']);
        $this->auctions->placeBid($this->user($request), $input['id'], $input['amount']);

        return $this->respond($request, ['ok' => true]);
    }

    public function buyNow(Request $request): JsonResponse
    {
        $input = $request->validate(['id' => 'required|integer|min:1']);
        $this->auctions->buyNow($this->user($request), $input['id']);

        return $this->respond($request, ['ok' => true]);
    }

    public function toggleSaved(Request $request): JsonResponse
    {
        $input = $request->validate(['userId' => 'sometimes|integer|min:1', 'kind' => ['required', Rule::in(['listing'])], 'id' => 'required|integer|min:1']);
        $user = $this->user($request);
        $this->assertOwnId($user, $input['userId'] ?? null);
        $saved = DB::transaction(function () use ($user, $input) {
            // Serialize toggles for the same account; the composite key also forbids duplicates.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $query = DB::table('listing_wishlists')->where('user_id', $user->id)->where('listing_id', $input['id']);
            if ($query->exists()) {
                $query->delete();

                return false;
            }
            $listing = $this->publicListing($input['id']);
            $price = DB::table('bids')->join('auctions', 'auctions.id', '=', 'bids.auction_id')
                ->where('auctions.listing_id', $listing->id)->max('bids.amount_rupiah') ?? $listing->price_rupiah;
            DB::table('listing_wishlists')->insert(['user_id' => $user->id, 'listing_id' => $listing->id, 'price_at_save_rupiah' => $price, 'saved_at' => now()]);

            return true;
        }, 5);

        return $this->respond($request, $saved);
    }

    public function openConversation(Request $request): JsonResponse
    {
        $input = $request->validate([
            'otherId' => 'required|integer|min:1', 'ref' => 'nullable|array:kind,id',
            'ref.kind' => ['required_with:ref', Rule::in(['listing', 'request'])], 'ref.id' => 'required_with:ref|integer|min:1',
        ]);
        $user = $this->user($request);
        $other = User::whereKey($input['otherId'])->where('status', 'active')->firstOrFail();
        abort_if((int) $other->id === (int) $user->id, 422, 'Choose another student to message.');
        $pair = [(int) $user->id, (int) $other->id];
        sort($pair, SORT_NUMERIC);
        $context = $input['ref'] ?? null;
        $columns = ['listing_id' => null, 'request_id' => null];
        if ($context && $context['kind'] === 'listing') {
            $listing = $this->publicListing($context['id']);
            abort_unless($listing->seller_id === $user->id || $listing->seller_id === $other->id, 403, 'The seller must be part of this conversation.');
            $columns['listing_id'] = $listing->id;
        } elseif ($context) {
            $wanted = WantedRequest::whereKey($context['id'])->where('removed', false)
                ->whereExists(fn ($query) => $query->selectRaw('1')->from('users')->whereColumn('users.id', 'wanted_requests.user_id')->where('users.status', 'active'))->firstOrFail();
            abort_unless($wanted->user_id === $user->id || $wanted->user_id === $other->id, 403, 'The requester must be part of this conversation.');
            $columns['request_id'] = $wanted->id;
        }
        $contextKey = implode(':', $pair).':'.($context ? $context['kind'].':'.$context['id'] : 'direct');
        $conversation = DB::transaction(function () use ($contextKey, $columns, $pair) {
            $conversation = Conversation::firstOrCreate(['context_key' => $contextKey], $columns);
            foreach ($pair as $userId) {
                DB::table('conversation_members')->insertOrIgnore(['conversation_id' => $conversation->id, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $conversation;
        }, 5);

        return $this->respond($request, $this->data->conversation($conversation));
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $input = $request->validate([
            'convId' => 'required|integer|min:1', 'msg' => 'required|array',
            'msg.type' => ['sometimes', Rule::in(['text', 'image', 'meetup'])],
            'msg.text' => 'nullable|string|max:1000', 'msg.src' => 'nullable|string|max:7500000',
            'msg.place' => 'nullable|string|max:160', 'msg.date' => 'nullable|date_format:Y-m-d', 'msg.time' => 'nullable|date_format:H:i',
        ]);
        $user = $this->user($request);
        $conversation = $this->memberConversation($user, $input['convId']);
        $type = $input['msg']['type'] ?? 'text';
        $message = $input['msg'];
        $path = null;
        $point = null;
        if ($type === 'text' && trim($message['text'] ?? '') === '') {
            throw ValidationException::withMessages(['msg.text' => 'Write a message.']);
        }
        if ($type === 'image') {
            if (empty($message['src'])) {
                throw ValidationException::withMessages(['msg.src' => 'Attach an image.']);
            }
            $path = $this->files->store($message['src'], 'chat', true);
        }
        if ($type === 'meetup') {
            $point = MeetupPoint::where('active', true)->where(function ($query) use ($message) {
                $query->where('short_name', $message['place'] ?? '')->orWhere('slug', $message['place'] ?? '');
            })->first();
            if (! $point || empty($message['date']) || empty($message['time'])) {
                throw ValidationException::withMessages(['msg.place' => 'Choose an active meet-up point, date and time.']);
            }
            $time = CarbonImmutable::createFromFormat('Y-m-d H:i', $message['date'].' '.$message['time'], 'Asia/Jakarta');
            if (! $time || ! $time->isFuture()) {
                throw ValidationException::withMessages(['msg.date' => 'Choose a future meet-up time in Asia/Jakarta.']);
            }
        }
        try {
            $created = DB::transaction(function () use ($user, $conversation, $message, $type, $path, $point) {
                $created = $this->writeMessage($user, $conversation, $type, $type === 'text' ? trim($message['text']) : null, $path);
                if ($type === 'meetup') {
                    MeetupProposal::create([
                        'message_id' => $created->id, 'meetup_point_id' => $point->id,
                        'proposed_date' => $message['date'], 'proposed_time' => $message['time'], 'timezone' => 'Asia/Jakarta', 'status' => 'pending',
                    ]);
                }

                return $created;
            }, 5);
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        $result = $this->data->message($created);

        return $this->respond($request, $result);
    }

    public function markConversationRead(Request $request): JsonResponse
    {
        $input = $request->validate(['convId' => 'required|integer|min:1']);
        $user = $this->user($request);
        $conversation = $this->memberConversation($user, $input['convId']);
        DB::transaction(function () use ($user, $conversation) {
            DB::table('messages')->where('conversation_id', $conversation->id)->select('id')->orderBy('id')->chunkById(200, function ($messages) use ($user) {
                DB::table('message_reads')->insertOrIgnore($messages->map(fn ($message) => ['message_id' => $message->id, 'user_id' => $user->id, 'read_at' => now()])->all());
            });
            DB::table('conversation_members')->where('conversation_id', $conversation->id)->where('user_id', $user->id)->update(['last_read_at' => now(), 'updated_at' => now()]);
        });

        return $this->respond($request, ['ok' => true]);
    }

    public function respondMeetup(Request $request): JsonResponse
    {
        $input = $request->validate(['convId' => 'required|integer|min:1', 'msgId' => 'required|integer|min:1', 'accept' => 'required|boolean']);
        $user = $this->user($request);
        $conversation = $this->memberConversation($user, $input['convId']);
        DB::transaction(function () use ($input, $user, $conversation) {
            $message = Message::whereKey($input['msgId'])->where('conversation_id', $conversation->id)->where('type', 'meetup')->lockForUpdate()->firstOrFail();
            abort_if((int) $message->sender_id === (int) $user->id, 403, 'Only the recipient can respond to a meet-up.');
            $proposal = MeetupProposal::where('message_id', $message->id)->lockForUpdate()->firstOrFail();
            if ($proposal->status !== 'pending') {
                throw ValidationException::withMessages(['msgId' => 'This meet-up has already been answered.']);
            }
            $proposal->update(['status' => $input['accept'] ? 'accepted' : 'declined', 'responded_by' => $user->id, 'responded_at' => now()]);
            $this->writeMessage($user, $conversation, 'text', $input['accept'] ? 'Meet-up accepted. See you there!' : 'I cannot make that meet-up. Can we pick another time?');
        }, 5);

        return $this->respond($request, ['ok' => true]);
    }

    public function markNotification(Request $request): JsonResponse
    {
        $input = $request->validate(['id' => 'required|integer|min:1', 'read' => 'sometimes|boolean']);
        $query = DB::table('notifications')->where('id', $input['id'])->where('user_id', $this->user($request)->id);
        abort_unless($query->exists(), 404);
        $query->update(['read_at' => ($input['read'] ?? true) ? now() : null, 'updated_at' => now()]);

        return $this->respond($request, ['ok' => true]);
    }

    public function markAllNotifications(Request $request): JsonResponse
    {
        $input = $request->validate(['userId' => 'sometimes|integer|min:1']);
        $user = $this->user($request);
        $this->assertOwnId($user, $input['userId'] ?? null);
        DB::table('notifications')->where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);

        return $this->respond($request, ['ok' => true]);
    }

    public function deleteNotification(Request $request): JsonResponse
    {
        $input = $request->validate(['id' => 'required|integer|min:1']);
        $deleted = DB::table('notifications')->where('id', $input['id'])->where('user_id', $this->user($request)->id)->delete();
        abort_unless($deleted > 0, 404);

        return $this->respond($request, ['ok' => true]);
    }

    public function messageImage(Request $request, Message $message): StreamedResponse
    {
        $this->memberConversation($this->user($request), $message->conversation_id);
        abort_unless($message->type === 'image' && $message->image_path && Storage::disk('local')->exists($message->image_path), 404);

        return Storage::disk('local')->response($message->image_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function writeMessage(User $user, Conversation $conversation, string $type, ?string $body = null, ?string $path = null): Message
    {
        $members = DB::table('conversation_members')->where('conversation_id', $conversation->id)->pluck('user_id');
        abort_unless($members->count() === 2 && $members->contains($user->id), 403);
        $otherId = (int) $members->first(fn ($id) => (int) $id !== (int) $user->id);
        abort_unless(User::whereKey($otherId)->where('status', 'active')->exists(), 422, 'This account is unavailable for messages.');
        $message = Message::create(['conversation_id' => $conversation->id, 'sender_id' => $user->id, 'type' => $type, 'body' => $body, 'image_path' => $path]);
        DB::table('message_reads')->insert(['message_id' => $message->id, 'user_id' => $user->id, 'read_at' => now()]);
        $conversation->touch();
        $preview = match ($type) {
            'image' => 'sent a photo.', 'meetup' => 'proposed a meet-up.', default => 'sent you a message.'
        };
        $this->auctions->notify($otherId, 'message', $user->name.' '.$preview, '/gm/messages.html?c='.$conversation->id, ['conversation_id' => $conversation->id]);

        return $message;
    }

    private function publicListing(int $id): Listing
    {
        return Listing::whereKey($id)->where('moderation', 'approved')->whereIn('type', ['marketplace', 'auction'])
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('users')->whereColumn('users.id', 'listings.seller_id')->where('users.status', 'active'))->firstOrFail();
    }

    private function memberConversation(User $user, int $id): Conversation
    {
        abort_unless(DB::table('conversation_members')->where('conversation_id', $id)->where('user_id', $user->id)->exists(), 403);

        return Conversation::findOrFail($id);
    }

    private function assertOwnId(User $user, ?int $id): void
    {
        abort_if($id !== null && $id !== (int) $user->id, 403);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->status === 'active', 403);

        return $user;
    }

    private function respond(Request $request, mixed $result): JsonResponse
    {
        return response()->json(['result' => $result, 'state' => $this->data->snapshot($this->user($request))]);
    }
}
