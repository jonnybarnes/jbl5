<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if(! Schema::hasTable('notes')) {
			Schema::create('notes', function(Blueprint $table)
			{
				$table->increments('id');
				$table->text('note');
				$table->string('reply_to')->nullable();
				$table->string('shorturl', 20);
				$table->integer('timestamp');
				$table->string('location');
				$table->tinyInt('photo');->nullable();
				$table->string('tweet_id')->nullable();
				$table->string('client_id')->nullable();
				$table->tinyInt('deleted')->default(0);
				$table->timestamps();
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
		//Schema::drop('notes');
	}

}
