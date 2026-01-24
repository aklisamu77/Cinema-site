<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Movie;

new class extends Component {
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $selectedGenre = null;
    public $selectedYear = null;
    public $sortBy = 'oldest';
    public $genres = [];
    public $years = [];

    public function mount()
    {
        // Get all genres
        $this->genres = \App\Models\Genre::orderBy('name')->pluck('name')->toArray();

        // Get all unique years from movies
        $this->years = Movie::whereNotNull('release_date')->selectRaw('YEAR(release_date) as year')->distinct()->orderBy('year', 'desc')->pluck('year')->filter()->toArray();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedGenre()
    {
        $this->resetPage();
    }

    public function updatingSelectedYear()
    {
        $this->resetPage();
    }

    public function updatingSortBy()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->selectedGenre = null;
        $this->selectedYear = null;
        $this->sortBy = 'oldest';
        $this->resetPage();
    }

    public function render()
    {
        $query = Movie::query();

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')->orWhere('original_title', 'like', '%' . $this->search . '%');
            });
        }

        // Apply genre filter
        if (!empty($this->selectedGenre)) {
            $query->whereHas('genres', function ($q) {
                $q->where('name', $this->selectedGenre);
            });
        }

        // Apply year filter
        if (!empty($this->selectedYear)) {
            $query->whereYear('release_date', $this->selectedYear);
        }

        // Apply sorting
        switch ($this->sortBy) {
            case 'latest':
                $query->orderBy('release_date', 'desc');
                break;
            case 'oldest':
                $query->orderBy('release_date', 'asc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'rating':
                $query->orderBy('vote_average', 'desc');
                break;
            default:
                $query->orderBy('release_date', 'asc');
        }

        return view('livewire.movies-component', [
            'movies' => $query->paginate(100),
        ]);
    }
};
?>
<div>
    <div class="min-h-screen bg-gray-50">
        {{-- Header Section --}}
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-8 px-4 shadow-lg">
            <div class="container mx-auto max-w-7xl">
                <h1 class="text-4xl font-bold mb-2">🎬 Movies Collection</h1>
                <p class="text-purple-100">Discover your next favorite movie</p>
            </div>
        </div>

        {{-- Main Content Container --}}
        <div class="container mx-auto max-w-7xl px-4 py-8">

            {{-- Search and Filter Section --}}
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">

                {{-- Search Bar --}}
                <div class="mb-6">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        Search Movies
                    </label>
                    <div class="relative">
                        <input type="text" id="search" wire:model.live.debounce.300ms="search"
                            placeholder="Search by title..."
                            class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <svg class="absolute left-4 top-3.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        {{-- Loading indicator --}}
                        <div wire:loading wire:target="search" class="absolute right-4 top-3.5">
                            <svg class="animate-spin h-5 w-5 text-purple-600" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Genre Filters --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Filter by Genre
                    </label>
                    <div class="flex flex-wrap gap-2">
                        {{-- All Genres Button --}}
                        <button wire:click="$set('selectedGenre', null)"
                            class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 {{ is_null($selectedGenre) ? 'bg-purple-600 text-white shadow-md' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            All Genres
                        </button>

                        {{-- Genre Filter Buttons (Dynamic) --}}
                        @foreach ($genres as $genre)
                            <button wire:click="$set('selectedGenre', '{{ $genre }}')"
                                class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 {{ $selectedGenre === $genre ? 'bg-purple-600 text-white shadow-md' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                {{ $genre }}
                            </button>
                        @endforeach
                    </div>
                </div>
                {{-- Year Filter --}}
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Year</label>
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="$set('selectedYear', null)"
                            class="px-4 py-2 rounded-full text-sm font-medium {{ is_null($selectedYear) ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                            All Years
                        </button>

                        @foreach ($years as $year)
                            <button wire:click="$set('selectedYear', '{{ $year }}')"
                                class="px-4 py-2 rounded-full text-sm font-medium {{ $selectedYear == $year ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                                {{ $year }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Active Filters Display --}}
                @if ($search || $selectedGenre)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm text-gray-600">Active filters:</span>

                            @if ($search)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-700">
                                    Search: "{{ $search }}"
                                    <button wire:click="$set('search', '')"
                                        class="ml-2 hover:text-purple-900">×</button>
                                </span>
                            @endif

                            @if ($selectedGenre)
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-700">
                                    Genre: {{ $selectedGenre }}
                                    <button wire:click="$set('selectedGenre', null)"
                                        class="ml-2 hover:text-purple-900">×</button>
                                </span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Results Count --}}
            <div class="mb-4 flex items-center justify-between flex-wrap gap-4">
                <p class="text-gray-600">
                    Showing <span class="font-semibold text-gray-900">{{ $movies->count() }}</span> of
                    <span class="font-semibold text-gray-900">{{ $movies->total() }}</span> movies
                </p>

                {{-- Sort Dropdown --}}
                <select wire:model.live="sortBy"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="latest">Latest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="title">Title (A-Z)</option>
                    <option value="rating">Highest Rated</option>
                </select>
            </div>

            {{-- Movies Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 mb-8">
                @forelse($movies as $movie)
                    <div
                        class="group bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">

                        {{-- Movie Poster --}}
                        <div class="relative aspect-[2/3] overflow-hidden bg-gray-200">
                            @if ($movie->poster_path)
                                <img src="{{ asset('storage/' . $movie->poster_path) }}"
                                    alt="{{ $movie->original_title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy">
                            @else
                                <div
                                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
                                    <svg class="w-16 h-16 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z">
                                        </path>
                                    </svg>
                                </div>
                            @endif

                            {{-- Hover Overlay --}}
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
                                <button
                                    class="w-full bg-white text-purple-600 py-2 rounded-lg font-semibold hover:bg-purple-600 hover:text-white transition">
                                    View Details
                                </button>
                            </div>

                            {{-- Rating Badge --}}
                            @if ($movie->vote_average)
                                <div
                                    class="absolute top-2 right-2 bg-yellow-400 text-gray-900 px-2 py-1 rounded-full text-xs font-bold flex items-center shadow-lg">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                        </path>
                                    </svg>
                                    {{ number_format($movie->vote_average, 1) }}
                                </div>
                            @endif
                        </div>

                        {{-- Movie Info --}}
                        <div class="p-4">
                            {{-- Title --}}
                            <h3
                                class="font-semibold text-gray-900 text-sm mb-2 line-clamp-2 group-hover:text-purple-600 transition-colors min-h-[2.5rem]">
                                {{ $movie->original_title }}
                            </h3>

                            {{-- Release Year --}}
                            <p class="text-xs text-gray-500 mb-2">
                                {{ optional($movie->release_date)->format('Y') ?? 'N/A' }}
                            </p>

                            {{-- Genres (Optional) --}}
                            @if ($movie->genres && count($movie->genres) > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach (array_slice($movie->genres, 0, 2) as $genre)
                                        <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                            {{ $genre }}
                                        </span>
                                    @endforeach
                                    @if (count($movie->genres) > 2)
                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
                                            +{{ count($movie->genres) - 2 }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    {{-- Empty State --}}
                    <div class="col-span-full py-16 text-center">
                        <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z">
                            </path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No movies found</h3>
                        <p class="text-gray-600 mb-4">Try adjusting your search or filters</p>
                        <button wire:click="resetFilters"
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                            Clear Filters
                        </button>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="flex justify-center">
                {{ $movies->links() }}
            </div>
        </div>
    </div>

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</div>
