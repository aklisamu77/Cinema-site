<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tmdb_id')->unique();

            $table->string('title')->nullable();
            $table->string('original_title')->nullable();
            $table->string('original_language', 10)->nullable();

            $table->text('overview')->nullable();

            $table->date('release_date')->nullable();

            $table->boolean('adult')->default(false);
            $table->boolean('video')->default(false);

            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();

            $table->decimal('popularity', 8, 4)->default(0);
            $table->decimal('vote_average', 3, 1)->default(0);
            $table->unsignedInteger('vote_count')->default(0);

            $table->char('origin_country', 2)->default('EG');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
