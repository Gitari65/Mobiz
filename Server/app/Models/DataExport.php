<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataExport extends Model
{
    protected $table = 'data_exports';
    protected $fillable = ['export_id', 'type', 'format', 'filename', 'status', 'requested_by', 'filters', 'error', 'completed_at'];
    protected $casts = ['filters' => 'array'];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
