<?php

use Livewire\Component;
use App\Services\ElCinemaSearchService;

new class extends Component
{
    public string $q = '';

    protected $queryString = ['q'];

    // Computed property: access as $this->results
    public function getResultsProperty(): array
    {
        $q = trim($this->q);

        if ($q === '') {
            return [];
        }

        return app(ElCinemaSearchService::class)->search($q); // should return array of items
    }
};
?>

<div style="max-width:900px;margin:20px auto;padding:0 12px;">
    <input
        type="text"
        wire:model.live.debounce.400ms="q"
        placeholder="Search ElCinema..."
        style="width:100%;padding:12px 14px;border:1px solid #ccc;border-radius:10px;outline:none;"
    >

    <div wire:loading style="margin-top:10px;color:#666;">
        Searching...
    </div>

    @if(trim($q) !== '')
        <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:12px;">
            @forelse($this->results as $item)
                <a href="{{ $item['link'] }}"
                   target="_blank"
                   rel="noopener"
                   style="width:170px;text-decoration:none;color:inherit;border:1px solid #e5e5e5;border-radius:14px;overflow:hidden;display:block;background:#fff;"
                >
                    <div style="width:100%;height:230px;background:#f6f6f6;display:flex;align-items:center;justify-content:center;">
                        @if(!empty($item['image']))
                            <img src="{{ $item['image'] }}"
                                 alt="{{ $item['title'] ?? '' }}"
                                 loading="lazy"
                                 style="width:100%;height:100%;object-fit:cover;display:block;">
                        @else
                            <span style="color:#888;font-size:13px;">No Image</span>
                        @endif
                    </div>

                    <div style="padding:10px 10px 12px;text-align:center;">
                        <div style="font-size:14px;font-weight:600;line-height:1.3;">
                            {{ $item['title'] ?? 'Untitled' }}
                        </div>
                    </div>
                </a>
            @empty
                <div style="margin-top:8px;color:#666;">No results.</div>
            @endforelse
        </div>
    @endif
</div>
