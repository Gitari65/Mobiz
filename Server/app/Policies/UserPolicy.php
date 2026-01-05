<?php
namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    protected function isSuper(User $user): bool
    {
        return isset($user->role) && strcasecmp($user->role->name ?? '', 'superuser') === 0;
    }

    public function viewAny(User $user): bool
    {
        return $this->isSuper($user);
    }

    public function view(User $user, User $model): bool
    {
        return $this->isSuper($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuper($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->isSuper($user);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->isSuper($user);
    }

    // Custom actions
    public function activate(User $user): bool
    {
        return $this->isSuper($user);
    }

    public function deactivate(User $user): bool
    {
        return $this->isSuper($user);
    }

    public function resetPassword(User $user): bool
    {
        return $this->isSuper($user);
    }
}
