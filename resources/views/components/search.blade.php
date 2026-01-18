<?php

use Livewire\Component;
use App\Models\Movie;
use App\Services\ElCinemaSearchService;

new class extends Component
{
    public string $q = '';

    // optional: keep query in URL
    protected $queryString = ['q'];

    public function updatedQ()
    {
        // runs when typing (you can keep empty, or do something)
    }

    public function getResultsProperty()
    {
        if (trim($this->q) === '') {
            return collect();
        }

        $service = new ElCinemaSearchService();
        $elCinemaResults = $service->search($this->q);
        dd($elCinemaResults);
        return $elCinemaResults;
        
 
    }
};
?>

<div style="max-width:600px;margin:20px auto;">
    <input
        type="text"
        wire:model.live.debounce.300ms="q"
        placeholder="Search Egyptian movies..."
        style="width:100%;padding:10px;border:1px solid #ccc;"
    >

    @if($q !== '')
        <div style="border:1px solid #ddd;margin-top:10px;padding:10px;">
            @forelse($this->results as $movie)
                <div style="padding:8px 0;border-bottom:1px solid #eee;">
                    <a href="{{ url('/movies/' . $movie->id) }}">
                        {{ $movie->title ?? $movie->name ?? 'Movie' }}
                    </a>
                </div>
            @empty
                <div>No results.</div>
            @endforelse
        </div>
    @endif
</div>
