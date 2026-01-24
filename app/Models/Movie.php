<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'tmdb_id',
        'title',
        'original_title',
        'original_language',
        'overview',
        'release_date',
        'adult',
        'video',
        'poster_path',
        'backdrop_path',
        'popularity',
        'vote_average',
        'vote_count',
        'origin_country',
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }
}

