<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->latest()
            ->paginate(15);

        return inertia('admin/users/Index', [
            'users' => $users,
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . User::ROLE_USER . ',' . User::ROLE_ADMIN],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'User role updated successfully.');
    }

    public function ban(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot ban yourself.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'You cannot ban an admin.');
        }

        $user->update(['banned_at' => now()]);

        return back()->with('success', 'User has been banned.');
    }

    public function unban(Request $request, User $user)
    {
        $user->update(['banned_at' => null]);

        return back()->with('success', 'User has been unbanned.');
    }

    public function updateTrustScore(Request $request, User $user)
    {
        $validated = $request->validate([
            'trust_score' => ['required', 'integer', 'min:-1000', 'max:1000'],
        ]);

        $user->update(['trust_score' => $validated['trust_score']]);

        return back()->with('success', 'User trust score updated.');
    }
}
