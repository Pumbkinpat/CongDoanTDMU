<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'department' => $u->department,
                'roleId' => $u->role_id,
                'roleName' => $u->role_name,
                'createdAt' => $u->created_at
            ];
        });

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('changeme123'),
            'role_id' => $request->roleId ?? 3,
            'department' => $request->department ?? 'TDMU'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã tạo tài khoản cán bộ mới thành công!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'roleId' => $user->role_id
            ]
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tài khoản thành công!'
        ]);
    }
}