<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    public const CREATED_AT = 'created_datetime';

    public const UPDATED_AT = 'updated_datetime';

    protected $table = 'cards';

    protected $primaryKey = 'card_id';

    protected $fillable = [
        'board_id',
        'column_id',
        'title',
        'content',
        'color',
        'sort_index',
        'is_archived',
    ];

    protected $casts = [
        'sort_index' => 'integer',
        'is_archived' => 'boolean',
    ];

    /**
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class, 'board_id', 'board_id');
    }

    /**
     * @return BelongsTo<BoardColumn, $this>
     */
    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'column_id', 'column_id');
    }
}
