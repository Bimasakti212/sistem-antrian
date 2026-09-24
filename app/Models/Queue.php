<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $fillable = ['queue_date', 'number', 'code', 'name', 'phone', 'service', 'status', 'registered_at', 'called_at', 'completed_at', 'registered_by'];
    protected function casts(): array
    {
        return ['queue_date' => 'date', 'registered_at' => 'datetime', 'called_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
