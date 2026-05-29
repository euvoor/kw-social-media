<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    function up() {
        $files = [
            __DIR__ . "/schema.sql",
        ];

        $patterns = [
            "fn_*.sql",
            "tr_*.sql",
            "tbl_*.sql",
            "v_*.sql",
        ];

        foreach ($patterns as $pattern) {
            $matches = glob(__DIR__ . "/{$pattern}");
            sort($matches);

            foreach ($matches as $match) {
                $files[] = $match;
            }
        }

        foreach ($files as $sqlfile) {
            if (! is_file($sqlfile)) {
                continue;
            }

            DB::unprepared(file_get_contents($sqlfile));
        }
    }

    /**
     * Reverse the migrations.
     */
    function down() {
        $drop = __DIR__ . "/drop.sql";

        if (is_file($drop)) {
            DB::unprepared(file_get_contents($drop));
        }
    }
};
