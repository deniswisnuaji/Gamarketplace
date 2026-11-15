<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedBigInteger('published_price_rupiah')->nullable();
        });
        DB::table('listings')->where('moderation', 'approved')->where('type', 'marketplace')
            ->update(['published_price_rupiah' => DB::raw('price_rupiah')]);
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('published_price_rupiah');
        });
    }
};
