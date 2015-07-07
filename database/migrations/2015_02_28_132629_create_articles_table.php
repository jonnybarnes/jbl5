<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateArticlesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if(! Schema::hasTable('articles')) {
			Schema::create('articles', function(Blueprint $table)
			{
				$table->increments('id');
				$table->string('titleurl', 50);
				$table->string('url', 120)->nullable();
				$table->string('shorturl', 20);
				$table->string('title');
				$table->longText('main');
				$table->text('tags');
				$table->integer('date_time');
				$table->tinyInt('published')->default(0);
				$table->softDeletes();
			});
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('articles');
	}

}
