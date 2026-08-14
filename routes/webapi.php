<?php

use Core\Explorer\Http\Controllers\ItemsController;
use Illuminate\Support\Facades\Route;
use Modules\SocialMedia\Http\Controllers\AccountController;
use Modules\SocialMedia\Services\PublishingConnectorService;

Route::prefix("/account")->group(function() {
    Route::get("/connector", [ AccountController::class, "show" ]);
    Route::put("/connector", [ AccountController::class, "update" ])
        ->defaults("kwerio.connector_fields", [
            "publishing_connection" => PublishingConnectorService::FIELD_DEFINITION,
        ]);
});

Route::post("/explorer/watch", [ ItemsController::class, "watch" ]);
Route::post("/explorer/unwatch", [ ItemsController::class, "unwatch" ]);
Route::post("/explorer/export", [ ItemsController::class, "export" ]);
Route::post("/explorer", [ItemsController::class, "get"]);
Route::post("/explorer/upsert", [ItemsController::class, "get_item"]);
Route::put("/explorer", [ ItemsController::class, "upsert" ]);
Route::delete("/explorer", [ ItemsController::class, "delete" ]);
Route::post("/explorer/import", [ ItemsController::class, "import" ]);
