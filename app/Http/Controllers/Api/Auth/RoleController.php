<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function assignRole(Request $request, User $user): JsonResponse
    {
        // Validasi input dari permintaan
        $request->validate([
            'role_id' => 'required|exists:roles,id', // Memastikan role_id ada di tabel roles
        ]);

        // Mengambil role berdasarkan role_id yang diberikan
        $role = Role::find($request->role_id);

        // Menetapkan role kepada pengguna
        $user->roles()->sync([$role->id]);

        return $this->responseJson(200, 'Role berhasil diberikan.', $user);
    }

}

