<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRecord extends Model
{
    protected $fillable = ['user_id', 'entity', 'record_id', 'data', 'client_updated_at', 'deleted_at', 'seq'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
