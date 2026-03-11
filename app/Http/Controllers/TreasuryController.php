<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreasuryTransactionRequest;
use App\Models\Customer;
use App\Models\FinancialTransaction;
use App\Models\Supplier;
use App\Models\Treasury;
use App\Services\TreasuryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

class TreasuryController extends Controller
{
    public function index(Request $request)
    {
        // 1. جلب الخزن النشطة لعرضها في الكروت العلوية
        $treasuries = Treasury::where('is_active', true)->get();

        // 2. بناء الاستعلام للحركات المالية
        $query = FinancialTransaction::with(['treasury', 'model', 'user']);

        // 3. تطبيق الفلاتر (البحث المتقدم)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('model', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        // 4. ترتيب النتائج
        $query->latest('transaction_date');

        // 5. التعامل مع التصدير (Excel / PDF)
        if ($request->filled('export')) {
            $exportData = $query->get();

            if ($request->export == 'excel') {
                return $this->exportToCsv($exportData);
            }
            if ($request->export == 'pdf') {
                return $this->generatePdf($exportData);
            }
        }

        // 6. العرض العادي (Pagination)
        $transactions = $query->paginate(20)->withQueryString();

        return view('treasury.index', compact('transactions', 'treasuries'));
    }

    public function create()
    {
        $treasuries = Treasury::where('is_active', true)->select('id', 'name')->get();
        $customers = Cache::remember('customers_list', 86400, fn () => Customer::select('id', 'name')->get());
        $suppliers = Cache::remember('suppliers_list', 86400, fn () => Supplier::select('id', 'name')->get());

        return view('treasury.create', compact('treasuries', 'customers', 'suppliers'));
    }

    public function store(TreasuryTransactionRequest $request, TreasuryService $service)
    {
        DB::beginTransaction();
        try {
            $service->processTransaction($request->validated());
            DB::commit();

            return response()->json(['message' => 'تم تسجيل السند وتحديث الرصيد بنجاح!'], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function edit($id)
    {
        $transaction = FinancialTransaction::findOrFail($id);
        $treasuries = Treasury::where('is_active', true)->select('id', 'name')->get();
        $customers = Cache::remember('customers_list', 86400, fn () => Customer::select('id', 'name')->get());
        $suppliers = Cache::remember('suppliers_list', 86400, fn () => Supplier::select('id', 'name')->get());

        return view('treasury.edit', compact('transaction', 'treasuries', 'customers', 'suppliers'));
    }

    public function update(TreasuryTransactionRequest $request, $id, TreasuryService $service)
    {
        DB::beginTransaction();
        try {
            $transaction = FinancialTransaction::findOrFail($id);
            $service->updateTransaction($transaction, $request->validated());
            DB::commit();

            return response()->json(['message' => 'تم تعديل السند بنجاح!'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id, TreasuryService $service)
    {
        DB::beginTransaction();
        try {
            $transaction = FinancialTransaction::findOrFail($id);
            $service->deleteTransaction($transaction);
            DB::commit();

            return redirect()->route('treasury.index')->with('success', 'تم حذف السند وتحديث رصيد الخزينة بنجاح.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('treasury.index')->with('error', $e->getMessage());
        }
    
    }

    // =========================================================================
    // دوال مساعدة للتصدير (Private Helpers)
    // =========================================================================

    private function exportToCsv($data)
    {
        return response()->streamDownload(function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // لدعم اللغة العربية

            fputcsv($file, ['رقم السند', 'التاريخ', 'نوع الحركة', 'الجهة', 'المبلغ', 'البيان', 'المستخدم']);

            foreach ($data as $row) {
                $type = $row->type == 'income' ? 'سند قبض' : 'سند صرف';
                $modelName = $row->model->name ?? 'حركة عامة / منصرفات';
                $userName = $row->user->name ?? 'نظام';

                fputcsv($file, [
                    $row->id,
                    $row->transaction_date,
                    $type,
                    $modelName,
                    $row->amount,
                    $row->description,
                    $userName,
                ]);
            }
            fclose($file);
        }, 'سجل_الخزينة_'.date('Y-m-d').'.csv');
    }

    private function generatePdf($data)
    {
        $table = "<table class='table table-bordered table-striped text-center' dir='rtl'>
                    <thead class='table-dark'>
                        <tr>
                            <th>رقم السند</th>
                            <th>التاريخ</th>
                            <th>نوع الحركة</th>
                            <th>الجهة</th>
                            <th>المبلغ</th>
                            <th>البيان</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($data as $row) {
            $type = $row->type == 'income' ? 'قبض' : 'صرف';
            $colorClass = $row->type == 'income' ? 'text-success' : 'text-danger';
            $modelName = $row->model->name ?? 'حركة عامة / منصرفات';
            $amount = number_format($row->amount, 2);
            $desc = $row->description ?? '-';

            $table .= "<tr>
                        <td class='fw-bold'>#{$row->id}</td>
                        <td dir='ltr'>{$row->transaction_date}</td>
                        <td class='{$colorClass} fw-bold'>{$type}</td>
                        <td>{$modelName}</td>
                        <td dir='ltr' class='fw-bold {$colorClass}'>{$amount}</td>
                        <td>{$desc}</td>
                       </tr>";
        }
        $table .= '</tbody></table>';

        $html = view('layouts.print_list')
            ->with('title', 'سجل حركات الخزينة')
            ->with('table', $table)
            ->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // يفضل A4-L (Landscape) للجداول العريضة
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);

        return response($mpdf->Output('', 'S'))->header('Content-Type', 'application/pdf');
    }
}
