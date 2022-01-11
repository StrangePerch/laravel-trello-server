<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BoardUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_user', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->unsigned()->index();
            $table->foreign("user_id")->references("id")->on("users");
            $table->bigInteger("board_id")->unsigned()->index();
            $table->foreign("board_id")->references("id")->on("boards");
            $table->bigInteger("owner_id")->unsigned()->index();
            $table->foreign("owner_id")->references("id")->on("users");
            $table->boolean("write");
            $table->boolean("edit");
            $table->boolean("delete");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_user');
    }
}
