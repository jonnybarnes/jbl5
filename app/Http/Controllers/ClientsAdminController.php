<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class ClientsAdminController extends Controller
{
    /**
     * Show a list of known clients
     *
     * @return \Illuminate\View\Factory view
     */
    public function listClients()
    {
        $clients = DB::table('clients')->get();

        return view('admin.listclients', array('clients' => $clients));
    }

    /**
     * Form to add a client name
     *
     * @return \Illuminate\View\Factory view
     */
    public function newClient()
    {
        return view('admin.newclient');
    }

    /**
     * Process the request to adda new client name
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postNewClient(Request $request)
    {
        $clientUrl = $request->input('client_url');
        $clientName = $request->input('client_name');
        DB::table('clients')->insert(
            array(
                'client_url' => $clientUrl,
                'client_name' => $clientName
            )
        );

        return view('admin.newclientsuccess');
    }

    /**
     * Show a form to edit a client name
     *
     * @param  string The client id
     * @return \Illuminate\View\Factory view
     */
    public function editClient($clientId)
    {
        $client = DB::table('clients')->where('id', $clientId)->first();

        return view('admin.editclient', array('id' => $clientId, 'client_url' => $client['client_url'], 'client_name' => $client['client_name']));
    }

    /**
     * Process the request to edit a client name
     *
     * @param  string  The client id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\Factory view
     */
    public function postEditClient($clientId, Request $request)
    {
        if ($request->input('edit')) {
            $clientUrl = $request->input('client_url');
            $clientName = $request->input('client_name');

            DB::table('clients')->where('id', $clientId)
                ->update(array(
                    'client_url' => $clientUrl,
                    'client_name' => $clientName
                ));

            return view('admin.editclientsuccess');
        } elseif ($request->input('delete')) {
            DB::table('clients')->where('id', $clientId)->delete();

            return view('admin.deleteclientsuccess');
        }
    }
}
