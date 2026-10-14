<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// T-02: book (data-model §3-§4).
// Motor aquí: PK, FK RESTRICT, isbn único parcial entre activos,
// ck_book_available_range. Pendiente T-18 (solo dominio hoy):
// ck_book_total_positive, ck_user_role NO se crean aquí.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('library.book', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 250);
            $table->string('author', 200);
            $table->char('isbn', 13);
            $table->uuid('category_id');
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->string('cover_key', 512)->nullable();
            $table->dateTimeTz('deleted_at')->nullable();

            $table->foreign('category_id')->references('id')->on('library.category')->restrictOnDelete();
            $table->check('ck_book_available_range', 'available_copies >= 0 AND available_copies <= total_copies');
        });

        DB::statement('CREATE UNIQUE INDEX uq_book_isbn_active ON library.book (isbn) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_book_category_title ON library.book (category_id, title) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('library.book');
    }
};
