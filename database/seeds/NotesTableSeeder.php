<?php

use Illuminate\Database\Seeder;

class NotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Note::class, 10)->create();
        $noteWithPlace = App\Note::create([
            'note' => 'Having a #beer at the local.'
        ]);
        $place = App\Place::find(1);
        $noteWithPlace->place()->associate($place);
        $noteWithPlace->save();
        $noteWithContact = App\Note::create([
            'note' => 'Hi @tantek'
        ]);
        $noteWithoutContact = App\Note::create([
            'note' => 'Hi @bob'
        ]);
    }
}
