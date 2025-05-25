<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserBalaceController extends Controller
{
    /**
     * @group Balance
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 {
     *  "id": 2,
     *  "user_id": 2,
     *  "balance": 0,
     *  "created_at": "2025-05-24T15:39:16.000000Z",
     *  "updated_at": "2025-05-24T16:00:13.000000Z"
     * }
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     * */
    public function get(Request $request)
    {
        $user = $request->user();
        return response()->json($user->balance);
    }
}
