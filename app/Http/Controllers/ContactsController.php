<?php namespace App\Http\Controllers;

use Illuminate\Routing\Conrtoller;
use Illuminate\Filesystem\Filesystem;
use App\Contact;

class ContactsController extends Controller
{
    //show all contacts
    public function showAll()
    {
        $fs = new Filesystem();
        $contacts = Contact::all();
        foreach ($contacts as $contact) {
            $contact->homepagePretty = parse_url($contact->homepage)['host'];
            $file = public_path() . '/assets/profile-images/' . $contact->homepagePretty . '/image';
            if ($fs->exists($file)) {
                $contact->image = '/assets/profile-images/' . $contact->homepagePretty . '/image';
            } else {
                $contact->image = '/assets/profile-images/default-image';
            }
        }

        return view('contacts', array('contacts' => $contacts));
    }

    //show single contact
    public function showSingle($nick)
    {
        $fs = new Filesystem();
        $contact = Contact::where('nick', '=', $nick)->firstOrFail();
        $contact->homepagePretty = parse_url($contact->homepage)['host'];
        $file = public_path() . '/assets/profile-images/' . $contact->homepagePretty . '/image';
        if ($fs->exists($file)) {
            $contact->image = '/assets/profile-images/' . $contact->homepagePretty . '/image';
        } else {
            $contact->image = '/assets/profile-images/default-image';
        }

        return view('contact', array('contact' => $contact));
    }
}
