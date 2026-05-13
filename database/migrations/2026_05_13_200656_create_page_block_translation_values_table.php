<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_block_translation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('page_blocks')->cascadeOnDelete();
            $table->string('locale', 12)->index();
            $table->string('field_key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['block_id', 'locale', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_block_translation_values');
    }
};
