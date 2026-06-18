<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Coupon extends Model
{
    use HasFactory;
    protected $table = 'rental_coupons';
    protected $guarded = [];
    protected $casts = ['valid_from' => 'datetime', 'valid_to' => 'datetime', 'is_active' => 'boolean'];
}
