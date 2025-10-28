<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModuleSetting;
use Illuminate\Http\Request;

class ModuleSettingController extends Controller
{
    public function index()
    {
        $modules = ModuleSetting::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $modules
        ]);
    }

    public function show($moduleName)
    {
        $module = ModuleSetting::where('module_name', $moduleName)->first();
        
        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $module
        ]);
    }

    public function toggle(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only superadmin can change module settings.'
            ], 403);
        }

        $module = ModuleSetting::findOrFail($id);

        $validated = $request->validate([
            'is_enabled' => 'required|boolean'
        ]);

        $module->is_enabled = $validated['is_enabled'];
        $module->save();

        return response()->json([
            'success' => true,
            'message' => 'Module status updated successfully',
            'data' => $module
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only superadmin can update module settings.'
            ], 403);
        }

        $module = ModuleSetting::findOrFail($id);

        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'is_enabled' => 'sometimes|boolean'
        ]);

        $module->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Module updated successfully',
            'data' => $module
        ]);
    }

    public function getEnabled()
    {
        $enabledModules = ModuleSetting::where('is_enabled', true)->pluck('module_name')->toArray();
        
        return response()->json([
            'success' => true,
            'data' => $enabledModules
        ]);
    }
}
