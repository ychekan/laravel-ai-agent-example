<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $session_key
 * @property string|null $last_question
 * @property string|null $rewritten_question
 * @property string|null $last_answer
 * @property array|null $sources
 *
 * @method static create(array $data)
 * @method static find(int $id)
 * @method static findOrFail(int $id)
 * @method static firstOrCreate(array $attributes, array $values = [])
 */
class ChatSession extends Model
{
    protected $fillable = [
        'session_key',
        'last_question',
        'rewritten_question',
        'last_answer',
        'sources',
    ];

    protected $casts = [
        'sources' => 'array',
    ];
}
