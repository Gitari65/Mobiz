<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key','value','category','description','is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public $timestamps = true;
}
