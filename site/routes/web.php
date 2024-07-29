<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

Route::get('/', function () {
    return redirect('/pdf');
});

Route::get('/html', function () {
    return view('html');
});

Route::get('/pdf', function () {
    $nodeExecutable = env('NODE_PATH') ?? '/usr/bin/node'; // dev servers using NVM or macs do not update /usr/bin folder
    $npmExecutable = env('NPM_PATH') ?? '/usr/bin/npm'; // dev servers using NVM or macs do not update /usr/bin folder

    Browsershot::html(View::make('html'))
        ->setNodeBinary($nodeExecutable)
        ->setNpmBinary($npmExecutable)
        ->timeout(300)
        ->addChromiumArguments([
            'font-render-hinting' => 'none',
            'force-color-profile' => 'srgb',
            'headless' => true,
        ])
        ->noSandbox()
        ->ignoreHttpsErrors()
        ->landscape()
        //->userAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36')
        ->setOption('headless', 'new')
        ->setOption('args', [
            '--disable-web-security',
            '--disable-gpu',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
        ])
        ->waitUntilNetworkIdle()
        ->showBackground()
        ->save(storage_path('demo.pdf'))
    ;

    echo 'Finished PDF generation - file located at: [PROJECT_ROOT]/site/storage/demo.pdf';
});
