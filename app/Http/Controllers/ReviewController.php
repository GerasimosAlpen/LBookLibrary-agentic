<?php

namespace App\Http\Controllers;

use App\Services\ReviewService;
use App\Http\Requests\StoreReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function index(int $bookId)
    {
        $reviews = $this->reviewService->getReviewsForBook($bookId);
        return response()->json($reviews); // or view depending on needs, but usually returned to view
    }

    public function store(StoreReviewRequest $request, int $bookId)
    {
        $this->reviewService->createReview($bookId, $request->validated(), Auth::user());
        return redirect()->back()->with('success', 'Review added successfully.');
    }

    public function destroy(int $bookId, int $reviewId)
    {
        $this->reviewService->deleteReview($bookId, $reviewId, Auth::user());
        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
