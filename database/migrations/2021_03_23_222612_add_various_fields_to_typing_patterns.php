<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVariousFieldsToTypingPatterns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('typing_patterns', function (Blueprint $table) {
            $table->integer('compared_samples')->nullable();
            $table->integer('previous_samples')->default(0);
            $table->integer('confidence')->default(0);
            $table->integer('score')->default(0);
            $table->integer('net_score')->default(0);
            $table->boolean('result');
            $table->boolean('success');
            $table->integer('message_code');
            $table->enum('position', [1, 2, 3, 4, 5, 6])->nullable();
            $table->enum('enrolled_position', [1, 2, 3, 4, 5, 6])->nullable();
            $table->enum('selected_position', [1, 2, 3, 4, 5, 6])->nullable();
            $table->string('custom_field')->nullable();
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
            $table->dropColumn("compared_samples");
            $table->dropColumn("previous_samples");
            $table->dropColumn("confidence");
            $table->dropColumn("score");
            $table->dropColumn("net_score");
            $table->dropColumn("result");
            $table->dropColumn("success");
            $table->dropColumn("message_code");
            $table->dropColumn("position");
            $table->dropColumn("enrolled_position");
            $table->dropColumn('selected_position');
            $table->dropColumn("custom_field");
        });
    }
}
