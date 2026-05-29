<?php

use Illuminate\Support\Facades\Route;
use Modules\SocialMedia\Http\Controllers\Controller;

Route::get("/", [Controller::class, "show_index_page"]);
Route::get("/upsert/{id}", [Controller::class, "show_index_page"]);
Route::get("/{any}", [Controller::class, "show_index_page"])->where("any", ".*");
/*! __PAGE_ROUTE__ !*/
