<?php

namespace App\Enums;

enum TransactionType: string
{
    case PURCHASE = 'purchase';           // فاتورة مشتريات (زيادة)
    case SALE = 'sale';                   // فاتورة مبيعات (نقص)
    case PRODUCTION_IN = 'prod_in';       // استلام من الإنتاج (زيادة)
    case PRODUCTION_OUT = 'prod_out';     // صرف للإنتاج (نقص)
    case ADJUSTMENT = 'adjustment';       // تعديل جرد عام

    // الأنواع الجديدة للحركة اليدوية من شاشة المخازن
    case MANUAL_ADD = 'manual_add';       // تسوية بالزيادة (إضافة يدوية)
    case MANUAL_DEDUCT = 'manual_deduct'; // تسوية بالعجز/توالف (خصم يدوي)
}
