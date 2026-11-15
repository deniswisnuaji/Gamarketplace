<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $t) {
            $t->string('code', 20)->primary();
            $t->string('name', 100);
            $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40)->unique();
            $t->string('name', 80);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('meetup_points', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40)->unique();
            $t->string('short_name', 80)->unique();
            $t->string('name', 160);
            $t->enum('kind', ['faculty', 'public']);
            $t->decimal('map_x', 5, 2)->nullable();
            $t->decimal('map_y', 5, 2)->nullable();
            $t->string('tip')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->enum('role', ['student', 'admin'])->default('student');
            $t->enum('status', ['active', 'suspended'])->default('active');
            $t->string('username', 40)->nullable()->unique();
            $t->string('faculty_code', 20)->nullable();
            $t->foreign('faculty_code')->references('code')->on('faculties')->restrictOnDelete();
            $t->foreignId('default_meetup_point_id')->nullable()->constrained('meetup_points')->nullOnDelete();
            $t->string('bio', 500)->nullable();
            $t->string('phone', 32)->nullable();
            $t->string('avatar_path')->nullable();
            $t->boolean('show_phone')->default(false);
            $t->boolean('show_faculty')->default(true);
            $t->timestamp('safety_agreed_at')->nullable();
        });
        Schema::create('user_preferences', function (Blueprint $t) {
            $t->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            foreach (['notif_messages', 'notif_offers', 'notif_outbid', 'notif_price_drop', 'notif_scam'] as $column) {
                $t->boolean($column)->default(true);
            }
            $t->boolean('notif_email')->default(false);
            $t->boolean('verified_only')->default(false);
            $t->timestamps();
        });
        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->foreignId('meetup_point_id')->constrained()->restrictOnDelete();
            $t->enum('type', ['marketplace', 'auction']);
            $t->string('title', 80);
            $t->enum('condition', ['like-new', 'good', 'fair']);
            $t->text('description');
            $t->unsignedBigInteger('price_rupiah');
            $t->boolean('negotiable')->default(false);
            $t->enum('moderation', ['pending', 'approved', 'rejected', 'hidden'])->default('pending');
            $t->enum('availability', ['available', 'reserved', 'sold'])->default('available');
            $t->unsignedBigInteger('views')->default(0);
            $t->text('moderation_reason')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['moderation', 'type', 'created_at']);
            $t->index(['category_id', 'meetup_point_id']);
        });
        Schema::create('listing_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->unsignedSmallInteger('position');
            $t->timestamps();
            $t->unique(['listing_id', 'position']);
        });
        Schema::create('moderation_actions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->restrictOnDelete();
            $t->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $t->string('from_status', 16);
            $t->string('to_status', 16);
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->index(['listing_id', 'created_at']);
        });
        Schema::create('auctions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->unique()->constrained()->restrictOnDelete();
            $t->unsignedSmallInteger('duration_days');
            $t->unsignedBigInteger('buy_now_rupiah')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamp('finalized_at')->nullable();
            $t->timestamps();
        });
        Schema::create('bids', function (Blueprint $t) {
            $t->id();
            $t->foreignId('auction_id')->constrained()->restrictOnDelete();
            $t->foreignId('bidder_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('amount_rupiah');
            $t->boolean('is_buy_now')->default(false);
            $t->timestamps();
            $t->index(['auction_id', 'created_at', 'id']);
        });
        Schema::table('auctions', function (Blueprint $t) {
            $t->foreignId('winner_bid_id')->nullable()->constrained('bids')->restrictOnDelete();
        });
        Schema::create('wanted_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->foreignId('meetup_point_id')->constrained()->restrictOnDelete();
            $t->string('title', 80);
            $t->unsignedBigInteger('budget_min_rupiah')->default(0);
            $t->unsignedBigInteger('budget_max_rupiah');
            $t->enum('condition', ['any', 'like-new', 'good']);
            $t->timestamp('needed_by');
            $t->text('description')->nullable();
            $t->boolean('found')->default(false);
            $t->boolean('removed')->default(false);
            $t->timestamps();
            $t->softDeletes();
            $t->index(['user_id', 'found']);
            $t->index(['category_id', 'needed_by']);
        });
        Schema::create('request_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained('wanted_requests')->cascadeOnDelete();
            $t->string('path');
            $t->unsignedSmallInteger('position');
            $t->timestamps();
            $t->unique(['request_id', 'position']);
        });
        Schema::create('request_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->constrained('wanted_requests')->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('item', 160);
            $t->unsignedBigInteger('price_rupiah');
            $t->enum('condition', ['like-new', 'good', 'fair']);
            $t->text('message')->nullable();
            $t->timestamps();
            $t->unique(['request_id', 'user_id']);
        });
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('request_id')->nullable()->constrained('wanted_requests')->restrictOnDelete();
            $t->string('context_key', 180)->unique();
            $t->timestamps();
        });
        Schema::create('conversation_members', function (Blueprint $t) {
            $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->timestamp('last_read_at')->nullable();
            $t->timestamps();
            $t->primary(['conversation_id', 'user_id']);
        });
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained()->restrictOnDelete();
            $t->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $t->enum('type', ['text', 'image', 'meetup'])->default('text');
            $t->text('body')->nullable();
            $t->string('image_path')->nullable();
            $t->timestamps();
            $t->index(['conversation_id', 'created_at', 'id']);
        });
        Schema::create('message_reads', function (Blueprint $t) {
            $t->foreignId('message_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->timestamp('read_at');
            $t->primary(['message_id', 'user_id']);
        });
        Schema::create('meetup_proposals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('message_id')->unique()->constrained()->restrictOnDelete();
            $t->foreignId('meetup_point_id')->constrained()->restrictOnDelete();
            $t->date('proposed_date');
            $t->time('proposed_time');
            $t->string('timezone', 64)->default('Asia/Jakarta');
            $t->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $t->foreignId('responded_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('responded_at')->nullable();
            $t->timestamps();
        });
        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $t->enum('kind', ['scam', 'bug', 'listing', 'user', 'other'])->default('scam');
            $t->foreignId('listing_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('subject_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('type', 24)->default('other');
            $t->string('channel', 40)->default('online');
            $t->string('place', 100)->nullable();
            $t->date('incident_date')->nullable();
            $t->string('title', 80);
            $t->text('description');
            $t->string('identifier')->nullable();
            $t->boolean('anonymous')->default(true);
            $t->enum('status', ['review', 'verified', 'dismissed'])->default('review');
            $t->enum('risk', ['low', 'medium', 'high'])->default('medium');
            $t->timestamp('verified_at')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('review_note')->nullable();
            $t->string('public_title', 80)->nullable();
            $t->text('public_summary')->nullable();
            $t->timestamps();
            $t->index(['status', 'risk', 'created_at']);
        });
        Schema::create('report_evidence', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained()->cascadeOnDelete();
            $t->string('private_path');
            $t->unsignedSmallInteger('position');
            $t->timestamps();
            $t->unique(['report_id', 'position']);
        });
        Schema::create('report_seen', function (Blueprint $t) {
            $t->foreignId('report_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->timestamp('seen_at');
            $t->primary(['report_id', 'user_id']);
        });
        Schema::create('listing_wishlists', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('price_at_save_rupiah')->nullable();
            $t->timestamp('saved_at');
            $t->primary(['user_id', 'listing_id']);
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type', 32);
            $t->text('text');
            $t->string('link')->nullable();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('request_id')->nullable()->constrained('wanted_requests')->nullOnDelete();
            $t->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'read_at', 'created_at']);
        });
        Schema::create('admin_activity', function (Blueprint $t) {
            $t->id();
            $t->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $t->string('kind', 24);
            $t->text('text');
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['admin_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['admin_activity', 'notifications', 'listing_wishlists', 'report_seen', 'report_evidence', 'reports', 'meetup_proposals', 'message_reads', 'messages', 'conversation_members', 'conversations', 'request_offers', 'request_photos', 'wanted_requests'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('auctions', fn (Blueprint $t) => $t->dropConstrainedForeignId('winner_bid_id'));
        foreach (['bids', 'auctions', 'moderation_actions', 'listing_photos', 'listings', 'user_preferences'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['faculty_code']);
            $t->dropConstrainedForeignId('default_meetup_point_id');
            $t->dropColumn(['role', 'status', 'username', 'faculty_code', 'bio', 'phone', 'avatar_path', 'show_phone', 'show_faculty', 'safety_agreed_at']);
        });
        foreach (['meetup_points', 'categories', 'faculties'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
