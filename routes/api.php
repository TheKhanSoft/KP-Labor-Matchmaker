<?php

use App\Models\Worker;
use App\Http\Resources\WorkerResource;
use Illuminate\Support\Facades\Route;

// Get a list of available workers
Route::get('/workers', function () {
    return WorkerResource::collection(Worker::where('is_available', true)->get());
});

// Get details of a single worker
Route::get('/workers/{worker}', function (Worker $worker) {
    return new WorkerResource($worker);
});
