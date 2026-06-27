<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$movies = App\Models\Movie::all(['title', 'slug', 'poster_path', 'poster_url', 'backdrop_path', 'backdrop_url']);
foreach ($movies as $movie) {
    echo "Title: {$movie->title}\n";
    echo "Slug: {$movie->slug}\n";
    echo "Poster: " . $movie->poster . "\n";
    echo "Backdrop: " . $movie->backdrop . "\n";
    echo "Has Backdrop: " . ($movie->has_backdrop ? 'Yes' : 'No') . "\n";
    echo "--------------------------\n";
}
