<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

class TokensAdminController extends Controller
{
    /**
     * Show all the saved tokens
     *
     * @return \Illuminate\View\Factory view
     */
    public function showTokens()
    {
        $tokensController = new TokensController();
        $tokens = $tokensController->getAll();

        return view('admin.listtokens', array('tokens' => $tokens));
    }

    /**
     * Show the form to delete a certain token
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function deleteToken($tokenId)
    {
        return view('admin.deletetoken', array('id' => $tokenId));
    }

    /**
     * Process the request to delete a token
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteToken($tokenId)
    {
        $tokensController = new TokensController();
        $tokensController->deleteToken($tokenId);

        return view('admin.deletetokensuccess', array('id' => $tokenId));
    }
}
