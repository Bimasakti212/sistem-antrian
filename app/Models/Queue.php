<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Queue extends Model
{
    protected $fillable = ['queue_date', 'number', 'code', 'category_id', 'service_counter_id', 'name', 'phone', 'service', 'source', 'status', 'registered_at', 'called_at', 'serving_at', 'completed_at', 'registered_by', 'called_by'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(QueueCategory::class, 'category_id');
    }

    public function serviceCounter(): BelongsTo
    {
        return $this->belongsTo(ServiceCounter::class);
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    protected function casts(): array
    {
        return ['queue_date' => 'date', 'registered_at' => 'datetime', 'called_at' => 'datetime', 'serving_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
