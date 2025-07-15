<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SoldeController extends Controller
{
    public function getSolde(Request $request)
    {
        return response()->json([
            'solde' => $request->user()->solde
        ]);
    }
}
