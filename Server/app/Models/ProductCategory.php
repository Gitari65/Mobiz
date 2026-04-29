<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description', 
        'icon',
        'company_id',
        'parent_id',
        'display_order',
        'created_by',
        'updated_by'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category', 'name');
    }
    
    // Parent-child relationships for subcategories
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id')->orderBy('display_order');
    }
    
    public function descendants()
    {
        return $this->children()->with('descendants');
    }
    
    public function allDescendants()
    {
        $descendants = collect();
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->allDescendants());
        }
        return $descendants;
    }
}
