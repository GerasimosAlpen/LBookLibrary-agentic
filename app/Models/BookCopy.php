<?php

namespace App\Models;

use App\Enums\CopyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCopy extends Model
{
    protected $fillable = [
        'book_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CopyStatus::class,
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'copy_id');
    }
}