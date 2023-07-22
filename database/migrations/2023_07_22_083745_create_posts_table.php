<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text("body")->nullable();
            $table->text("title")->nullable();
            $table->longText("media_url")->nullable();
            $table->string("total_likes")->default(0);
            $table->string("total_comments")->default(0);
            $table->string("posted_by")->default(0);
            $table->date("posted_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
