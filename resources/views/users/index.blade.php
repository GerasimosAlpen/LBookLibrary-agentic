@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto mt-8 p-6 bg-white rounded shadow dark:bg-gray-800">
    <h2 class="text-2xl font-bold mb-4 dark:text-white">All Users</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <table class="w-full text-left border-collapse">
        <thead>
            <tr>
                <th class="border-b py-2 dark:text-gray-200">Name</th>
                <th class="border-b py-2 dark:text-gray-200">Email</th>
                <th class="border-b py-2 dark:text-gray-200">Role</th>
                <th class="border-b py-2 dark:text-gray-200">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td class="border-b py-2 dark:text-gray-300">{{ $user->name }}</td>
                <td class="border-b py-2 dark:text-gray-300">{{ $user->email }}</td>
                <td class="border-b py-2 dark:text-gray-300">{{ $user->role->value }}</td>
                <td class="border-b py-2 flex space-x-2">
                    <a href="{{ route('users.show', $user->id) }}" class="text-blue-600 hover:underline">View</a>
                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
