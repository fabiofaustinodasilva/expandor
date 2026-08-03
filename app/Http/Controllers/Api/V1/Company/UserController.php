<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Domains\Company\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with('role:id,name,slug')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Users listed successfully.',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total(),
            ],
        ]);
    }
}
