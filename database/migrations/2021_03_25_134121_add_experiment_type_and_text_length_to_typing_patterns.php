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
            $table->string('text_length')->nullable();
            $table->string('experiment_type')->nullable();
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
