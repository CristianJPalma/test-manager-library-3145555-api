<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// T-02: loan + loan_item (data-model §3-§5).
// FK-2 loan_item.loan_id CASCADE (composición, nunca se dispara: no se borran préstamos).
// FK-1/3/4/5 RESTRICT. category_name SIN FK a propósito (ADR-004).
// Único (loan_id, book_id) motor. Sin columnas status/days_late (Art. VII).
return new class extends Migration {
    public function up(): void
    {
        Schema::create('library.loan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('loaned_by_user_id');
            $table->dateTimeTz('loaned_at');

            $table->foreign('member_id')->references('id')->on('library.member')->restrictOnDelete();
            $table->foreign('loaned_by_user_id')->references('id')->on('library.user')->restrictOnDelete();
        });

        Schema::create('library.loan_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('book_id');
            $table->string('book_title', 250);
            $table->char('isbn', 13);
            $table->string('category_name', 120);
            $table->dateTimeTz('due_at');
            $table->dateTimeTz('returned_at')->nullable();

            $table->foreign('loan_id')->references('id')->on('library.loan')->cascadeOnDelete();
            $table->foreign('book_id')->references('id')->on('library.book')->restrictOnDelete();
            $table->unique(['loan_id', 'book_id']);
        });

        DB::statement('CREATE INDEX ix_loan_loaned_at ON library.loan (loaned_at)');
        DB::statement('CREATE INDEX ix_loan_item_book ON library.loan_item (book_id)');
        DB::statement('CREATE INDEX ix_loan_item_due_open ON library.loan_item (due_at) WHERE returned_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('library.loan_item');
        Schema::dropIfExists('library.loan');
    }
};
