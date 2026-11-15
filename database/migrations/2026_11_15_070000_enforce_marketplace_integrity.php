<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @return array<string, array<string, string>> */
    private function checks(): array
    {
        return [
            'listings' => ['price_range' => 'price_rupiah BETWEEN 1 AND 1000000000000', 'nonnegative_views' => 'views >= 0'],
            'listing_photos' => ['position_range' => 'position BETWEEN 0 AND 3'],
            'request_photos' => ['position_range' => 'position BETWEEN 0 AND 2'],
            'report_evidence' => ['position_range' => 'position BETWEEN 0 AND 2'],
            'wanted_requests' => ['budget_range' => 'budget_min_rupiah >= 0 AND budget_max_rupiah >= budget_min_rupiah AND budget_max_rupiah BETWEEN 1 AND 1000000000000'],
            'request_offers' => ['price_range' => 'price_rupiah BETWEEN 1 AND 1000000000000'],
            'bids' => ['amount_range' => 'amount_rupiah BETWEEN 1 AND 1000000000000'],
            'auctions' => ['duration_range' => 'duration_days IN (1,3,5,7)', 'buy_now_range' => 'buy_now_rupiah IS NULL OR buy_now_rupiah BETWEEN 1 AND 1000000000000'],
            'conversations' => ['one_context' => 'listing_id IS NULL OR request_id IS NULL'],
            'reports' => ['public_scam_only' => "status <> 'verified' OR kind = 'scam'"],
            'meetup_points' => ['map_range' => '(map_x IS NULL OR map_x BETWEEN 0 AND 100) AND (map_y IS NULL OR map_y BETWEEN 0 AND 100)'],
            'messages' => ['payload_type' => "(type = 'text' AND body IS NOT NULL AND image_path IS NULL) OR (type = 'image' AND image_path IS NOT NULL AND body IS NULL) OR (type = 'meetup' AND body IS NULL AND image_path IS NULL)"],
        ];
    }

    public function up(): void
    {
        foreach ($this->checks() as $table => $checks) {
            foreach ($checks as $name => $expression) {
                $constraint = $table.'_'.$name;
                if (DB::getDriverName() === 'sqlite') {
                    // SQLite cannot ALTER ADD CHECK. Equivalent triggers enforce direct SQL writes too.
                    preg_match_all('/\b[a-z][a-z_]+\b/', $expression, $matches);
                    $columns = array_filter(array_unique($matches[0]), fn ($word) => ! in_array($word, ['text', 'image', 'meetup', 'verified', 'scam']));
                    $newExpression = $expression;
                    foreach ($columns as $column) {
                        $newExpression = preg_replace('/\b'.preg_quote($column, '/').'\b/', 'NEW.'.$column, $newExpression);
                    }
                    foreach (['INSERT', 'UPDATE'] as $event) {
                        $trigger = $constraint.'_'.strtolower($event);
                        DB::statement("CREATE TRIGGER {$trigger} BEFORE {$event} ON {$table} WHEN NOT ({$newExpression}) BEGIN SELECT RAISE(ABORT, '{$constraint}'); END");
                    }
                } else {
                    DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} CHECK ({$expression})");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->checks() as $table => $checks) {
            foreach ($checks as $name => $_) {
                $constraint = $table.'_'.$name;
                if (DB::getDriverName() === 'sqlite') {
                    foreach (['insert', 'update'] as $event) {
                        DB::statement("DROP TRIGGER IF EXISTS {$constraint}_{$event}");
                    }
                } else {
                    DB::statement("ALTER TABLE {$table} DROP CHECK {$constraint}");
                }
            }
        }
    }
};
