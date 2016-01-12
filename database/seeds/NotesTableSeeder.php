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
        $note = App\Note::create([
            'note' => 'A note with a #beer at the local.'
        ]);
        $place = App\Place::find(1);
        $note->place()->associate($place);
        $note->save();
    }
}
