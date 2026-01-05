<?php
namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\AuditLog;
use Illuminate\Support\Arr;

class ModelActivityObserver
{
    protected function record(Model $model, string $action)
    {
        $user = Auth::user();
        AuditLog::create([
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'ip_address' => request()->ip() ?? null,
            'old_values' => $action === 'updated' ? Arr::except($model->getOriginal(), ['updated_at']) : null,
            'new_values' => $action !== 'deleted' ? Arr::except($model->getAttributes(), ['updated_at']) : null,
            'notes' => null
        ]);
    }

    public function created(Model $model) { $this->record($model, 'created'); }
    public function updated(Model $model) { $this->record($model, 'updated'); }
    public function deleted(Model $model) { $this->record($model, 'deleted'); }
}
