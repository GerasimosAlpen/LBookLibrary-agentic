@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-8 p-6 bg-white rounded shadow dark:bg-gray-800">
    <h2 class="text-2xl font-bold mb-4 dark:text-white">Notifications</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    @if($notifications->isEmpty())
        <p class="text-gray-500 dark:text-gray-400">You have no notifications.</p>
    @else
        <ul class="space-y-4">
            @foreach($notifications as $notification)
            <li class="p-4 rounded border {{ $notification->is_read ? 'bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700' : 'bg-blue-50 dark:bg-blue-900 border-blue-200 dark:border-blue-700' }}">
                <div class="flex justify-between items-center">
                    <p class="dark:text-gray-200">{{ $notification->message }}</p>
                    @if(!$notification->is_read)
                        <form action="{{ route('users.notifications.read', $notification->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Mark as Read</button>
                        </form>
                    @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">Read</span>
                    @endif
                </div>
                <span class="text-xs text-gray-400 mt-2 block">{{ $notification->created_at->diffForHumans() }}</span>
            </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
