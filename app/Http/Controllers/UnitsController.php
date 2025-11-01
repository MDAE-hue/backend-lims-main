<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UnitsController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('units')->get());
    }

    public function show($id)
    {
        $unit = DB::table('units')->where('id', $id)->first();
        if (!$unit) return response()->json(['error' => 'Unit not found'], 404);
        return response()->json($unit);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:units,name',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $id = DB::table('units')->insertGetId(['name' => $request->name]);

        return response()->json(['id' => $id, 'name' => $request->name], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:units,name,'.$id,
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $updated = DB::table('units')->where('id', $id)->update(['name' => $request->name]);
        if (!$updated) return response()->json(['error' => 'Unit not found'], 404);

        return response()->json(['id' => $id, 'name' => $request->name]);
    }

    public function destroy($id)
    {
        $deleted = DB::table('units')->where('id', $id)->delete();
        if (!$deleted) return response()->json(['error' => 'Unit not found'], 404);
        return response()->json(['message' => 'Unit deleted successfully']);
    }
}
