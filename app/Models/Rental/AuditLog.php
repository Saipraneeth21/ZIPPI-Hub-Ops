<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
class AuditLog extends Model
{
    protected $table = 'rental_audit_logs';
    protected $guarded = [];
    protected $casts = ['before' => 'array', 'after' => 'array'];
}
