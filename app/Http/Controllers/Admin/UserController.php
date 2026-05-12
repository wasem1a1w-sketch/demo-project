<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $query = User::query();

        if (request('role') === 'client') {
            $query->whereHas('roles', fn($q) => $q->where('name', 'client'));
        } elseif (request('role') === 'non-client') {
            $query->whereDoesntHave('roles', fn($q) => $q->where('name', 'client'));
        }

        $users = $query->with('roles')->orderByDesc('id')->paginate(15);
        $roles = Role::with('permissions:id,name')->withCount('users')->orderBy('name')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'allRoles' => $roles,
            'filter' => request('role', ''),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load('roles');

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'userRole' => $user->roles->first()?->name ?? '',
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (!empty($data['password'])) {
            $user->update(['password' => bcrypt($data['password'])]);
        }

        $user->syncRoles(array_filter([$data['role'] ?? null]));

        return redirect()->route('admin.users')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }
}
