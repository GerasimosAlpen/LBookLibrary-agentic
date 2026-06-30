@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto mt-8 p-6 bg-white rounded shadow dark:bg-gray-800">
    <h2 class="text-2xl font-bold mb-4 dark:text-white">Borrowing History</h2>

    @if($history->isEmpty())
        <p class="text-gray-500 dark:text-gray-400">No borrowing history found.</p>
    @else
        <table class="w-full text-left border-collapse">
            <thead>
                <tr>
                    <th class="border-b py-2 dark:text-gray-200">Book</th>
                    <th class="border-b py-2 dark:text-gray-200">Borrow Date</th>
                    <th class="border-b py-2 dark:text-gray-200">Return Date</th>
                    <th class="border-b py-2 dark:text-gray-200">Status</th>
                    <th class="border-b py-2 dark:text-gray-200">Fine</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $transaction)
                <tr>
                    <td class="border-b py-2 dark:text-gray-300">
                        {{ $transaction->copy->book->title ?? 'Unknown Book' }}
                    </td>
                    <td class="border-b py-2 dark:text-gray-300">{{ $transaction->borrow_date }}</td>
                    <td class="border-b py-2 dark:text-gray-300">{{ $transaction->return_date ?? '-' }}</td>
                    <td class="border-b py-2 dark:text-gray-300">{{ $transaction->status->value }}</td>
                    <td class="border-b py-2 dark:text-gray-300">
                        @if($transaction->fine_amount > 0)
                            <span class="text-red-500">${{ number_format($transaction->fine_amount, 2) }}</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
