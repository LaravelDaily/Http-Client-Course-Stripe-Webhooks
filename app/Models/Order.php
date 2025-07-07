<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_order_id',
        'stripe_payment_intent_id',
        'customer_email',
        'amount',
        'currency',
        'status',
        'ticket_quantity',
        'ticket_type',
        'visit_date',
        'paid_at',
        'canceled_at',
        'failure_reason',
        'payment_details',
        'tickets',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'canceled_at' => 'datetime',
        'visit_date' => 'date',
        'payment_details' => 'array',
        'tickets' => 'array',
    ];

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'payment_failed';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
