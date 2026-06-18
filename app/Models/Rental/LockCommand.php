<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
class LockCommand extends Model
{
    protected $table = 'rental_lock_commands';
    protected $guarded = [];
}
