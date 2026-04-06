<?php

use Illuminate\Support\Facades\Route;
use LaravelUi5\OData\Http\Controller\OData;

Route::any('{path?}', [OData::class, 'handle'])
     ->where([
         'path' => '.*',
     ]);
