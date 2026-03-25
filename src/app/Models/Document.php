<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $indexed_at
 *
 * @method static create(array $data)
 * @method static find(int $documentId)
 * @method static findOrFail(int $documentId)
 */
class Document extends Model
{
    protected $fillable = [
        'title',
        'file_path',
        'mime_type',
        'file_size',
        'status',
        'error_message',
        'indexed_at',
    ];

    protected $casts = [
        'indexed_at' => 'datetime',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(Chunk::class);
    }
}
