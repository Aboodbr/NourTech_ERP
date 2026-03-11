<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Warehouse;
use App\Models\Treasury;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $setting = Setting::first() ?? new Setting();
        $warehouses = Warehouse::all();
        $treasuries = Treasury::all();

        return view('settings.index', compact('setting', 'warehouses', 'treasuries'));
    }

    /**
     * Update or create the settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'currency' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'default_warehouse' => 'nullable|exists:warehouses,id',
            'default_treasury' => 'nullable|exists:treasuries,id',
        ]);

        $setting = Setting::first() ?? new Setting();

        $data = $request->except(['company_logo']);

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logos', 'public');
            $data['company_logo'] = $path;
        }

        $setting->fill($data);
        $setting->save();

        return redirect()->route('settings.index')->with('success', 'تم حفظ الإعدادات بنجاح.');
    }
}
