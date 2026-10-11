<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardColumn extends Model
{
    public const CREATED_AT = 'created_datetime';

    public const UPDATED_AT = 'updated_datetime';

    protected $table = 'board_columns';

    protected $primaryKey = 'column_id';

    protected $fillable = [
        'board_id',
        'title',
        'color',
        'ordinal_position',
    ];

    protected $casts = [
        'ordinal_position' => 'integer',
    ];

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id', 'board_id');
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'column_id', 'column_id')
            ->orderBy('sort_index', 'asc');
    }
}
