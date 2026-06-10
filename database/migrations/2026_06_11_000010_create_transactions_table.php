<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TransactionStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('copy_id')
                ->constrained('book_copies')
                ->cascadeOnDelete();

            $table->dateTime('borrow_date');

            $table->dateTime('due_date');

            $table->dateTime('return_date')
                ->nullable();

            $table->enum(
                'status',
                array_column(TransactionStatus::cases(), 'value')
            )->default(TransactionStatus::ACTIVE->value);

            $table->integer('fine_amount')
                ->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};