<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExperimentTypeAndTextLengthToTypingPatterns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('typing_patterns', function (Blueprint $table) {
            $table->enum('text_length', ['short', 'medium', 'default', 'long', 'veryLong'])->nullable();
            $table->enum('experiment_type', ['default', 'length', 'swipe'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('typing_patterns', function (Blueprint $table) {
            $table->dropColumn("text_length");
            $table->dropColumn("experiment_type");
        });
    }
}
