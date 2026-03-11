<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'stock_id',
        'type', // purchase, sale, adjustment...
        'quantity',
        'user_id',
        'reference_type',
        'reference_id',
        'notes',
    ];

    // تحويل نوع الحركة تلقائياً للـ Enum
    protected $casts = [
        'type' => TransactionType::class,
    ];

    // العلاقة مع الرصيد
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    // العلاقة مع المستخدم الذي قام بالحركة
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // علاقة متعددة الأشكال (Polymorphic) لربط الحركة بمصدرها (فاتورة، أمر شغل)
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
