<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotesTest extends TestCase
{
    /**
     * Test the `/notes` page returns 200, this should
     * mean the database is being hit.
     *
     * @return void
     */
    public function testNotesPage()
    {
        $this->visit(config('app.url') . '/notes')
             ->assertResponseOk();
    }

    public function testSpecificNote()
    {
        $this->visit(config('app.url') . '/notes/B')
             ->see('#beer');
    }
}
