<?php

use Core\Explorer\Http\Controllers\ItemsController;
use Illuminate\Support\Facades\Route;

Route::post("/explorer/watch", [ ItemsController::class, "watch" ]);
Route::post("/explorer/unwatch", [ ItemsController::class, "unwatch" ]);
Route::post("/explorer/export", [ ItemsController::class, "export" ]);
Route::post("/explorer", [ItemsController::class, "get"]);
Route::post("/explorer/upsert", [ItemsController::class, "get_item"]);
Route::put("/explorer", [ ItemsController::class, "upsert" ]);
Route::delete("/explorer", [ ItemsController::class, "delete" ]);
Route::post("/explorer/import", [ ItemsController::class, "import" ]);
