<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_block_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('page_blocks')->cascadeOnDelete();
            $table->string('type');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->json('settings_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_block_items');
    }
};
