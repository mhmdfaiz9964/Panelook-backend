<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', '!=', 'customer')->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,admin,sales_manager,inventory_manager,purchase_manager,content_manager,support_agent',
        ]);
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        return response()->json(['success' => true, 'data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|in:super_admin,admin,sales_manager,inventory_manager,purchase_manager,content_manager,support_agent',
        ]);
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user() && $request->user()->id == $id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
        }
        User::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'User deleted']);
    }
}
