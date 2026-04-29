<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    use HasFactory;

    protected $table = 'business_categories';

    protected $fillable = [
        'name', 
        'description', 
        'icon',
        'parent_id',
        'display_order'
    ];
    
    // Parent-child relationships for subcategories
    public function parent()
    {
        return $this->belongsTo(BusinessCategory::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(BusinessCategory::class, 'parent_id')->orderBy('display_order');
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
