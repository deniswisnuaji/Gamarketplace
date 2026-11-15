<?php

namespace App\Services;

use App\Models\AdminActivity;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\MarketplaceNotification;
use App\Models\MeetupPoint;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Models\WantedRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MarketplaceData
{
    private function ms(\DateTimeInterface|string|null $date): ?int
    {
        return $date ? Carbon::parse($date)->getTimestampMs() : null;
    }

    private function url(?string $path): string
    {
        return app(MarketplaceFiles::class)->url($path);
    }

    /** @return array<string, mixed> */
    public function snapshot(?User $viewer): array
    {
        if ($viewer?->status !== 'active') {
            $viewer = null;
        }
        $admin = $viewer?->role === 'admin';
        $listings = Listing::with(['seller', 'category', 'point', 'photos', 'auction.bids'])->visibleTo($viewer)->latest()->get();
        $requests = WantedRequest::with(['user', 'category', 'point', 'photos', 'offers'])->where(function ($q) use ($viewer, $admin) {
            if (! $admin) {
                $q->where(fn ($p) => $p->where('removed', false)->whereHas('user', fn ($u) => $u->where('status', 'active')));
                if ($viewer) {
                    $q->orWhere('user_id', $viewer->id);
                }
            }
        })->latest()->get();
        $reports = Report::with(['evidence', 'seen'])->where(function ($q) use ($viewer, $admin) {
            if (! $admin) {
                $q->where(fn ($p) => $p->where('status', 'verified')->where('kind', 'scam'));
                if ($viewer) {
                    $q->orWhere('reporter_id', $viewer->id);
                }
            }
        })->latest()->get();
        $conversations = $viewer ? Conversation::with(['members', 'messages.reads', 'messages.meetup.point'])->whereHas('members', fn ($q) => $q->where('users.id', $viewer->id))->latest('updated_at')->get() : collect();

        return [
            'currentUserId' => $viewer ? (string) $viewer->id : null,
            'users' => User::all()->map(fn ($u) => $this->user($u, $viewer))->values()->all(),
            'listings' => $listings->map(fn ($l) => $this->listing($l))->all(),
            'requests' => $requests->map(fn ($r) => $this->wanted($r, $viewer))->all(),
            'reports' => $reports->map(fn ($r) => $this->report($r, $viewer))->all(),
            'conversations' => $conversations->map(fn ($c) => $this->conversation($c))->all(),
            'notifications' => $viewer ? MarketplaceNotification::where('user_id', $viewer->id)->latest()->get()->map(fn ($n) => ['id' => (string) $n->id, 'userId' => (string) $n->user_id, 'type' => $n->type, 'text' => $n->text, 'link' => $n->link ?? '', 'read' => (bool) $n->read_at, 'at' => $this->ms($n->created_at)])->all() : [],
            'adminLog' => $admin ? AdminActivity::latest()->limit(500)->get()->map(fn ($a) => ['id' => (string) $a->id, 'adminId' => (string) $a->admin_id, 'kind' => $a->kind, 'text' => $a->text, 'at' => $this->ms($a->created_at)])->all() : [],
        ];
    }

    /** @return array<string, mixed> */
    public function user(User $u, ?User $viewer): array
    {
        $private = $viewer && ($viewer->id === $u->id || $viewer->role === 'admin');
        $prefs = DB::table('user_preferences')->where('user_id', $u->id)->first();
        $map = ['notifMessages' => 'notif_messages', 'notifOffers' => 'notif_offers', 'notifOutbid' => 'notif_outbid', 'notifPriceDrop' => 'notif_price_drop', 'notifScam' => 'notif_scam', 'notifEmail' => 'notif_email', 'verifiedOnly' => 'verified_only'];
        $preferences = [];
        foreach ($map as $key => $column) {
            $preferences[$key] = $prefs ? (bool) $prefs->$column : ! in_array($key, ['notifEmail', 'verifiedOnly']);
        }

        return [
            'id' => (string) $u->id, 'name' => $u->name, 'username' => $u->username ?? '', 'role' => $u->role, 'status' => $u->status,
            'email' => $private ? $u->email : '', 'faculty' => $private || $u->show_faculty ? ($u->faculty_code ?? '') : '',
            'tikum' => MeetupPoint::find($u->default_meetup_point_id)->short_name ?? '', 'bio' => $u->bio ?? '',
            'phone' => $private || $u->show_phone ? ($u->phone ?? '') : '', 'photo' => $this->url($u->avatar_path),
            'showPhone' => (bool) $u->show_phone, 'showFaculty' => (bool) $u->show_faculty, 'createdAt' => $this->ms($u->created_at),
            'verified' => (bool) $u->email_verified_at, 'prefs' => $private ? $preferences : [],
            'wishlist' => $private ? DB::table('listing_wishlists')->where('user_id', $u->id)->get()->map(fn ($w) => ['kind' => 'listing', 'id' => (string) $w->listing_id, 'at' => $this->ms($w->saved_at), 'price' => $w->price_at_save_rupiah === null ? null : (int) $w->price_at_save_rupiah])->all() : [],
        ];
    }

    /** @return array<string, mixed> */
    public function listing(Listing $l): array
    {
        $a = $l->auction;

        return [
            'id' => (string) $l->id, 'sellerId' => (string) $l->seller_id, 'type' => $l->type, 'title' => $l->title, 'category' => $l->category->slug,
            'condition' => $l->condition, 'desc' => $l->description, 'tikum' => $l->point->short_name, 'photos' => $l->photos->map(fn ($p) => $this->url($p->path))->all(),
            'price' => (int) $l->price_rupiah, 'nego' => (bool) $l->negotiable, 'moderation' => $l->moderation, 'availability' => $l->availability, 'views' => (int) $l->views,
            'reason' => $l->moderation_reason ?? '', 'createdAt' => $this->ms($l->created_at), 'updatedAt' => $this->ms($l->updated_at), 'approvedAt' => $this->ms($l->approved_at),
            'auction' => $a ? ['durationDays' => (int) $a->duration_days, 'buyNow' => $a->buy_now_rupiah === null ? null : (int) $a->buy_now_rupiah, 'endsAt' => $this->ms($a->ends_at), 'finalized' => (bool) $a->finalized_at, 'winnerBidId' => $a->winner_bid_id ? (string) $a->winner_bid_id : null, 'bids' => $a->bids->map(fn ($b) => ['id' => (string) $b->id, 'userId' => (string) $b->bidder_id, 'amount' => (int) $b->amount_rupiah, 'at' => $this->ms($b->created_at), 'buyNow' => (bool) $b->is_buy_now])->all()] : null,
        ];
    }

    /** @return array<string, mixed> */
    public function wanted(WantedRequest $r, ?User $viewer = null): array
    {
        $offers = $r->offers;
        if (! $viewer || ($viewer->role !== 'admin' && $viewer->id !== $r->user_id)) {
            $offers = $offers->where('user_id', $viewer?->id);
        }

        return ['id' => (string) $r->id, 'userId' => (string) $r->user_id, 'title' => $r->title, 'category' => $r->category->slug, 'tikum' => $r->point->short_name, 'min' => (int) $r->budget_min_rupiah, 'max' => (int) $r->budget_max_rupiah, 'condition' => $r->condition, 'neededBy' => $this->ms($r->needed_by), 'desc' => $r->description ?? '', 'found' => (bool) $r->found, 'removed' => (bool) $r->removed, 'createdAt' => $this->ms($r->created_at), 'photos' => $r->photos->map(fn ($p) => $this->url($p->path))->all(), 'offerCount' => $r->offers->count(), 'offers' => $offers->values()->map(fn ($o) => ['id' => (string) $o->id, 'userId' => (string) $o->user_id, 'item' => $o->item, 'price' => (int) $o->price_rupiah, 'condition' => $o->condition, 'msg' => $o->message ?? '', 'at' => $this->ms($o->created_at)])->all()];
    }

    /** @return array<string, mixed> */
    public function report(Report $r, ?User $viewer): array
    {
        $private = $viewer && ($viewer->role === 'admin' || $viewer->id === $r->reporter_id);

        return ['id' => (string) $r->id, 'kind' => $r->kind, 'reporterId' => $private || ! $r->anonymous ? (string) $r->reporter_id : null, 'type' => $r->type, 'channel' => $r->channel, 'place' => $private ? ($r->place ?? '') : '', 'date' => $r->incident_date?->format('Y-m-d'), 'title' => $private ? $r->title : ($r->public_title ?? 'Verified marketplace safety warning'), 'desc' => $private ? $r->description : ($r->public_summary ?? 'A safety report was reviewed by the marketplace team. Follow safe trading practices and report suspicious activity.'), 'ident' => $private ? ($r->identifier ?? '') : '', 'anonymous' => (bool) $r->anonymous, 'status' => $r->status, 'risk' => $r->risk, 'createdAt' => $this->ms($r->created_at), 'verifiedAt' => $this->ms($r->verified_at), 'seenCount' => $r->seen->count(), 'seenBy' => $r->seen->filter(fn ($u) => $viewer && $u->id === $viewer->id)->map(fn ($u) => (string) $u->id)->values()->all(), 'evidence' => $private ? $r->evidence->map(fn ($e) => url('/gm/api/reports/evidence/'.$e->id))->all() : [], 'reviewNote' => $private ? ($r->review_note ?? '') : '', 'publicTitle' => $private ? $r->public_title : null, 'publicSummary' => $private ? $r->public_summary : null, 'listingId' => $private && $r->listing_id ? (string) $r->listing_id : null, 'subjectUserId' => $private && $r->subject_user_id ? (string) $r->subject_user_id : null];
    }

    /** @return array<string, mixed> */
    public function conversation(Conversation $c): array
    {
        return ['id' => (string) $c->id, 'members' => $c->members->map(fn ($u) => (string) $u->id)->all(), 'ref' => $c->listing_id ? ['kind' => 'listing', 'id' => (string) $c->listing_id] : ($c->request_id ? ['kind' => 'request', 'id' => (string) $c->request_id] : null), 'createdAt' => $this->ms($c->created_at), 'updatedAt' => $this->ms($c->updated_at), 'messages' => $c->messages->map(fn ($m) => $this->message($m))->all()];
    }

    /** @return array<string, mixed> */
    public function message(Message $m): array
    {
        $meetup = $m->meetup;

        return ['id' => (string) $m->id, 'from' => (string) $m->sender_id, 'type' => $m->type, 'text' => $m->body ?? '', 'src' => $m->image_path ? url('/gm/api/messages/'.$m->id.'/image') : '', 'at' => $this->ms($m->created_at), 'readBy' => $m->reads->map(fn ($u) => (string) $u->id)->all(), 'place' => $meetup?->point?->short_name, 'date' => $meetup?->proposed_date, 'time' => $meetup ? substr($meetup->proposed_time, 0, 5) : null, 'status' => $meetup?->status];
    }
}
