<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localized_routes', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 12)->index();
            $table->string('path');
            $table->string('routable_type');
            $table->unsignedBigInteger('routable_id');
            $table->string('route_name')->nullable();
            $table->timestamps();

            $table->unique(['locale', 'path']);
            $table->index(['routable_type', 'routable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localized_routes');
    }
};
