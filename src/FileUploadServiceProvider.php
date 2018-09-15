<?php

namespace Zoomyboy\FileUpload;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Support\Facades\Route;

class ServiceProvider extends BaseServiceProvider {
    public function boot() {
        Route::post('/zoomyboy/file-upload', 'Zoomyboy\FileUpload\Controllers\UploadController@handleUpload');
    }
}
