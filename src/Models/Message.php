<?php
declare(strict_types=1);


namespace RlKafka\Models;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

/**
 * @property string $uuid
 * @property string $topic
 * @property ?string $key
 * @property ?string $event_type
 * @property array $payload
 * @property string $status
 *
 * @method static static create(array $attributes)
 */
class Message extends Model
{
    protected $table = 'kafka_log';
    protected $primaryKey = 'uuid';

    protected $casts = [
        'payload' => 'array',
    ];

    protected $fillable = [
        'uuid', 'topic', 'key', 'event_type', 'payload', 'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Uuid::uuid4());
        });
    }
}
