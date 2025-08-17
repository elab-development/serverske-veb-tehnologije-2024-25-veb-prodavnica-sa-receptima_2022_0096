<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Get(
 *   path="/api/ping",
 *   summary="Health check",
 *   tags={"Health"},
 *   @OA\Response(response=200, description="pong")
 * )
 */
class HealthController extends Controller
{
    public function ping(Request $request)
    {
        return response()->json(['pong' => true]);
    }
}