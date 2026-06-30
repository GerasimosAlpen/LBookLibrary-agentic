@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto mt-8 p-6 bg-white rounded shadow dark:bg-gray-800">
    <h2 class="text-2xl font-bold mb-4 dark:text-white">Recommended for You</h2>

    @if($recommendations->isEmpty())
        <p class="text-gray-500 dark:text-gray-400">No recommendations available at the moment.</p>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($recommendations as $book)
            <div class="border rounded p-4 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                <h3 class="text-xl font-bold dark:text-white">{{ $book->title }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">By {{ $book->author->name ?? 'Unknown' }}</p>
                <div class="mt-4">
                    <a href="{{ route('books.show', $book->id) }}" class="text-blue-600 hover:underline">View Book</a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
