<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function getTestTypes()
    {
        $data = DB::table('test_types')->get();

        return response()->json($data);
    }

    public function getMethods()
    {
        $data = DB::table('methods')->get();

        return response()->json($data);
    }

    public function getStandards()
    {
        $data = DB::table('standards')->get();

        return response()->json($data);
    }

    public function getUnits()
    {
        $data = DB::table('units')->get();

        return response()->json($data);
    }

    public function storeTestDetail(Request $request)
    {
        $validated = $this->validate($request, [
            'report_id' => 'required|integer|exists:laboratory_report,id',
            'bahan_pengujian' => 'nullable|string|max:100',
            'test_type_id' => 'nullable|integer|exists:test_types,id',
            'method_id' => 'nullable|integer|exists:methods,id',
            'standard_id' => 'nullable|integer|exists:standards,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'result' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $id = DB::table('test_details')->insertGetId([
            'report_id' => $validated['report_id'],
            'bahan_pengujian' => $validated['bahan_pengujian'],
            'test_type_id' => $validated['test_type_id'],
            'method_id' => $validated['method_id'],
            'standard_id' => $validated['standard_id'],
            'unit_id' => $validated['unit_id'],
            'result' => $validated['result'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Test detail berhasil disimpan',
            'id' => $id,
        ], 201);
    }

    public function getTestDetails($report_id)
    {
        $data = DB::table('test_details')
            ->where('report_id', $report_id)
            ->get();

        return response()->json($data);
    }

    public function getTestDetailsView($report_id)
    {
        $data = DB::table('test_details')
            ->leftJoin('test_types', 'test_details.test_type_id', '=', 'test_types.id')
            ->leftJoin('methods', 'test_details.method_id', '=', 'methods.id')
            ->leftJoin('standards', 'test_details.standard_id', '=', 'standards.id')
            ->leftJoin('units', 'test_details.unit_id', '=', 'units.id')
            ->select(
                'test_details.id',
                'test_details.report_id',
                'test_details.bahan_pengujian',
                'test_details.result',
                'test_details.description',
                'test_types.name as test_type',
                'methods.name as method',
                'standards.name as standard',
                'units.name as unit'
            )
            ->where('report_id', $report_id)
            ->get();

        return response()->json($data);
    }

    public function updateTestDetail(Request $request, $id)
    {
        $validated = $this->validate($request, [
            'bahan_pengujian' => 'required|string|max:100',
            'test_type_id' => 'required|integer|exists:test_types,id',
            'method_id' => 'required|integer|exists:methods,id',
            'standard_id' => 'required|integer|exists:standards,id',
            'unit_id' => 'required|integer|exists:units,id',
            'result' => 'required|string',
            'description' => 'nullable|string',
        ]);

        DB::table('test_details')->where('id', $id)->update($validated);

        return response()->json(['message' => 'Test detail berhasil diperbarui']);
    }
}
