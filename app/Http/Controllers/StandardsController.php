<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StandardsController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('standards')->get());
    }

    public function show($id)
    {
        $standard = DB::table('standards')->where('id', $id)->first();
        if (!$standard) return response()->json(['error' => 'Standard not found'], 404);
        return response()->json($standard);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:standards,name',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $id = DB::table('standards')->insertGetId(['name' => $request->name]);

        return response()->json(['id' => $id, 'name' => $request->name], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:standards,name,'.$id,
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $updated = DB::table('standards')->where('id', $id)->update(['name' => $request->name]);
        if (!$updated) return response()->json(['error' => 'Standard not found'], 404);

        return response()->json(['id' => $id, 'name' => $request->name]);
    }

    public function destroy($id)
    {
        $deleted = DB::table('standards')->where('id', $id)->delete();
        if (!$deleted) return response()->json(['error' => 'Standard not found'], 404);
        return response()->json(['message' => 'Standard deleted successfully']);
    }
}
