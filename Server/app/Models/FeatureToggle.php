<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureToggle extends Model
{
    protected $fillable = ['key', 'name', 'enabled'];
    public $timestamps = false;
}
