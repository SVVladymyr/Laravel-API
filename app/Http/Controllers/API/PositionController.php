<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Position;

class PositionController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $positions = Position::all();
        
        if(!empty($positions))
        {
            return response()->json([
                "success" => true,
                "positions" => $positions
            ], 200);
        } else {
            return response()->json([
                "success" => true,
                "message" => "Positions not found"
            ], 422);
        }
        
       
    }
}
