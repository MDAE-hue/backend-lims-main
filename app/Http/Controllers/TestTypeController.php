<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestTypeController extends Controller
{
    // GET /api/test-types
    public function index()
    {
        $data = DB::table('test_types')->get();
        return response()->json($data);
    }

    // GET /api/test-types/{id}
    public function show($id)
    {
        $type = DB::table('test_types')->where('id', $id)->first();
        if (!$type) {
            return response()->json(['error' => 'Test type not found'], 404);
        }
        return response()->json($type);
    }

    // POST /api/test-types
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:test_types,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $id = DB::table('test_types')->insertGetId([
            'name' => $request->name
        ]);

        return response()->json(['id' => $id, 'name' => $request->name], 201);
    }

    // PUT /api/test-types/{id}
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:test_types,name,'.$id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = DB::table('test_types')->where('id', $id)->update([
            'name' => $request->name
        ]);

        if (!$updated) {
            return response()->json(['error' => 'Test type not found'], 404);
        }

        return response()->json(['id' => $id, 'name' => $request->name]);
    }

    // DELETE /api/test-types/{id}
    public function destroy($id)
    {
        $deleted = DB::table('test_types')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['error' => 'Test type not found'], 404);
        }
        return response()->json(['message' => 'Test type deleted successfully']);
    }
}
