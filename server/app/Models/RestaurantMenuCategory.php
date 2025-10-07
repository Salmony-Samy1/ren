<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantMenuCategory extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'is_active', 'display_order'];

    public function menuItems() {
        return $this->hasMany(RestaurantMenuItem::class, 'restaurant_menu_category_id');
    }
}