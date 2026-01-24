<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Genre;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncEgyptMoviesFromTmdb extends Command
{
    protected $signature = 'tmdb:sync-egypt-movies';
    protected $description = 'Sync Egyptian movies from TMDB and store images locally';

    public function handle(): int
    {
        $this->info('🎬 TMDB Egypt Movies Sync Started');

        $token = config('services.tmdb.token');

        if (!$token) {
            $this->error('❌ TMDB token not found in .env');
            return Command::FAILURE;
        }

        $page = 1;
        $totalPages = 1;

        do {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get('https://api.themoviedb.org/3/discover/movie', [
                    'with_origin_country' => 'EG',
                    'sort_by' => 'release_date.asc',
                    'page' => $page,
                ]);

            if ($response->failed()) {
                $this->error("❌ Request failed on page {$page}");
                return Command::FAILURE;
            }

            $data = $response->json();
            $totalPages = $data['total_pages'] ?? 1;

            DB::transaction(function () use ($data) {
                foreach ($data['results'] as $item) {

                    $posterPath = $this->downloadImage(
                        $item['poster_path'] ?? null,
                        'movies/posters'
                    );

                    $backdropPath = $this->downloadImage(
                        $item['backdrop_path'] ?? null,
                        'movies/backdrops'
                    );

                    $movie = Movie::updateOrCreate(
                        ['tmdb_id' => $item['id']],
                        [
                            'title' => $item['title'] ?? null,
                            'original_title' => $item['original_title'] ?? null,
                            'original_language' => $item['original_language'] ?? null,
                            'overview' => $item['overview'] ?? null,
                            'release_date' => $item['release_date'] ?: null,
                            'adult' => $item['adult'] ?? false,
                            'video' => $item['video'] ?? false,
                            'poster_path' => $posterPath,
                            'backdrop_path' => $backdropPath,
                            'popularity' => $item['popularity'] ?? 0,
                            'vote_average' => $item['vote_average'] ?? 0,
                            'vote_count' => $item['vote_count'] ?? 0,
                            'origin_country' => 'EG',
                        ]
                    );

                    if (!empty($item['genre_ids'])) {
                        $genreIds = Genre::whereIn('tmdb_id', $item['genre_ids'])
                            ->pluck('id')
                            ->toArray();

                        $movie->genres()->syncWithoutDetaching($genreIds);
                    }
                }
            });

            $this->info("✅ Page {$page} synced");
            $page++;

            usleep(250000);

        } while ($page <= $totalPages);

        $this->info('🎉 TMDB Egypt Movies Sync Finished Successfully');

        return Command::SUCCESS;
    }

    /**
     * Download image from TMDB and store locally
     */
    private function downloadImage(?string $tmdbPath, string $directory): ?string
    {
        if (!$tmdbPath) {
            return null;
        }

        $filename = Str::uuid() . '.jpg';
        $url = "https://image.tmdb.org/t/p/w500{$tmdbPath}";

        try {
            $image = Http::get($url)->body();

            Storage::disk('public')->put("{$directory}/{$filename}", $image);

            return "{$directory}/{$filename}";
        } catch (\Exception $e) {
            return null;
        }
    }
}
