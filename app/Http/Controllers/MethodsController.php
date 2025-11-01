<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MethodsController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('methods')->get());
    }

    public function show($id)
    {
        $method = DB::table('methods')->where('id', $id)->first();
        if (!$method) return response()->json(['error' => 'Method not found'], 404);
        return response()->json($method);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:methods,name',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $id = DB::table('methods')->insertGetId(['name' => $request->name]);

        return response()->json(['id' => $id, 'name' => $request->name], 201);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:methods,name,'.$id,
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $updated = DB::table('methods')->where('id', $id)->update(['name' => $request->name]);
        if (!$updated) return response()->json(['error' => 'Method not found'], 404);

        return response()->json(['id' => $id, 'name' => $request->name]);
    }

    public function destroy($id)
    {
        $deleted = DB::table('methods')->where('id', $id)->delete();
        if (!$deleted) return response()->json(['error' => 'Method not found'], 404);
        return response()->json(['message' => 'Method deleted successfully']);
    }
}
