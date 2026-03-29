<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'sku',
        'name',
        'type',
        'unit',
        'min_stock',
        'is_ordered',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'is_ordered' => 'boolean',
    ];

    // العلاقة مع الأرصدة (المخزون)
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    // --- الإضافات الجديدة الخاصة بالتصنيع ---

    // 1. علاقة المنتج بمعادلة تصنيعه (كل منتج تام له معادلة واحدة)
    public function billOfMaterial(): HasOne
    {
        return $this->hasOne(BillOfMaterial::class);
    }

    // 2. علاقة المنتج بأوامر التصنيع (المنتج يمكن أن يُطلب تصنيعه مرات عديدة)
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    // 3. (اختياري) علاقة لجلب النواقص كما فعلنا سابقاً
    public function scopeLowStock($query)
    {
        return $query->whereHas('stocks', function ($q) {
            $q->selectRaw('sum(quantity) as total_qty')
                ->groupBy('product_id')
                ->havingRaw('total_qty <= products.min_stock');
        });
    }

    public function getIsLowStockAttribute()
    {
        return $this->stocks()->sum('quantity') <= $this->min_stock;
    }
}
