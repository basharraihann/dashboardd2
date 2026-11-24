<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoNomorSetting extends Model
{
    protected $fillable = ['template', 'nomor_awal'];

    public static function current(): ?self
    {
        return static::query()->first();
    }
}