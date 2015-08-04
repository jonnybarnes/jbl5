<?php

namespace App\Http\Controllers;

class TokensAdminController extends Controller
{
    /**
     * Show all the saved tokens.
     *
     * @return \Illuminate\View\Factory view
     */
    public function showTokens()
    {
        $tokensController = new TokensController();
        $tokens = $tokensController->getAll();

        return view('admin.listtokens', ['tokens' => $tokens]);
    }

    /**
     * Show the form to delete a certain token.
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function deleteToken($tokenId)
    {
        return view('admin.deletetoken', ['id' => $tokenId]);
    }

    /**
     * Process the request to delete a token.
     *
     * @param  string The token id
     * @return \Illuminate\View\Factory view
     */
    public function postDeleteToken($tokenId)
    {
        $tokensController = new TokensController();
        $tokensController->deleteToken($tokenId);

        return view('admin.deletetokensuccess', ['id' => $tokenId]);
    }
}
