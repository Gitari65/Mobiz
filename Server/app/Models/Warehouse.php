<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'user_id', 'type'];

    // A warehouse can have many products (stock)
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // A warehouse can have many users assigned
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Helper: Generate warehouse name for user
    public static function generateNameForUser($user)
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $user->name), 0, 6));
        return $base . "_WH";
    }
}
