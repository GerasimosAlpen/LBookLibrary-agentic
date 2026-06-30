<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        if (Auth::user()->role->value !== 'ADMIN') {
            abort(403, 'Unauthorized action.');
        }
        $users = $this->userService->getAllUsers();
        return view('users.index', compact('users'));
    }

    public function show(int $id)
    {
        $user = $this->userService->getUser($id);
        
        if (Auth::user()->role->value !== 'ADMIN' && Auth::user()->id !== $id) {
            abort(403, 'Unauthorized action.');
        }

        return view('users.show', compact('user'));
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $this->userService->updateUser($id, $request->validated(), Auth::user());
        return redirect()->route('users.show', $id)->with('success', 'Profile updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->userService->deleteUser($id, Auth::user());
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function history()
    {
        $history = $this->userService->getBorrowingHistory(Auth::id());
        return view('users.history', compact('history'));
    }

    public function recommendations()
    {
        $recommendations = $this->userService->getRecommendations(Auth::id());
        return view('users.recommendations', compact('recommendations'));
    }
}
