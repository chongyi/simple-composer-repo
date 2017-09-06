<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackageDistArchivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package_dist_archives', function (Blueprint $table) {
            $table->increments('id');
            $table->string('package_name')->index()->comment('包名称');
            $table->string('version')->comment('版本号');
            $table->string('origin');
            $table->string('local');
            $table->string('hash')->index()->comment('哈希值');
            $table->string('type');
            $table->timestamps();

            $table->index(['package_name', 'version'], 'p_v_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('package_dist_archives');
    }
}
