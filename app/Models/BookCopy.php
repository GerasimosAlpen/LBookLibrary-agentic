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

    protected $appends = ['barcode'];

    protected function casts(): array
    {
        return [
            'status' => CopyStatus::class,
        ];
    }

    /**
     * Virtual barcode derived from the copy's primary key.
     * Format: COPY-00001
     */
    public function getBarcodeAttribute(): string
    {
        return 'COPY-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
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