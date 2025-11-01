<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    // GET /api/departments
    public function index()
    {
        try {
            $departments = Department::all();
            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch departments',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /api/departments/{id}
    public function show($id)
    {
        try {
            $dept = Department::findOrFail($id);
            return response()->json($dept);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Department not found',
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    // POST /api/departments
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $department = Department::create($request->only(['name', 'head_id']));
            return response()->json($department, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create department',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // PUT /api/departments/{id}
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        try {
            $department = Department::findOrFail($id);
            $department->update($request->only(['name', 'head_id']));
            return response()->json($department);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update department',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // DELETE /api/departments/{id}
    public function destroy($id)
    {
        try {
            $department = Department::findOrFail($id);
            $department->delete();
            return response()->json(['message' => 'Department deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete department',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
