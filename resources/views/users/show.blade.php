@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-8 p-6 bg-white rounded shadow dark:bg-gray-800">
    <h2 class="text-2xl font-bold mb-4 dark:text-white">User Profile</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border rounded p-2 dark:bg-gray-700 dark:text-white">
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 dark:text-gray-300 font-bold mb-2">Role</label>
            <input type="text" value="{{ $user->role->value }}" disabled class="w-full border rounded p-2 bg-gray-100 dark:bg-gray-600 dark:text-gray-300">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Update Profile</button>
    </form>
</div>
@endsection
