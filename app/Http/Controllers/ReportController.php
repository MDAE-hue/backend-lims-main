<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class ReportController extends Controller
{
    protected function generateReportNumber()
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;

        $bulan_romawi = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];
        $romawi = $bulan_romawi[$month];

        $lastReport = Report::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastReport) {
            $lastNumber = intval(substr($lastReport->no_report, 0, 3));
            $nomor_urut = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nomor_urut = '001';
        }

        return "{$nomor_urut}/AR/TEC-LAB/{$romawi}/{$year}";
    }

    public function index()
{
    try {
        // Ambil semua data report beserta relasinya
        $reports = Report::with([
            'requester',
            'department',
            'status',
            'samplerUser',
            'analystUser',
            'reviewedBy',
            'acknowledgeBy',
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        // Jika tidak ada data
        if ($reports->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada data laporan yang ditemukan.',
                'data' => []
            ], 200);
        }

        // Mapping data tambahan
        $reports->each(function ($report) {
            $report->sampler_name = $report->samplerUser?->name ?? null;
            $report->analyst_name = $report->analystUser?->name ?? null;
            $report->reviewed_by_name = $report->reviewedBy?->name ?? null;
            $report->acknowledge_by_name = $report->acknowledgeBy?->name ?? null;
            $report->requested_by_name = $report->requester?->name ?? null;
        });

        // Return sukses
        return response()->json([
            'message' => 'Data laporan berhasil diambil.',
            'data' => $reports
        ], 200);

    } catch (ModelNotFoundException $e) {
        // Jika model tidak ditemukan
        Log::error('Report model not found: ' . $e->getMessage());
        return response()->json([
            'error' => 'Data laporan tidak ditemukan.',
        ], 404);
    } catch (Exception $e) {
        // Error umum lainnya
        Log::error('Error saat mengambil laporan: ' . $e->getMessage());
        return response()->json([
            'error' => 'Terjadi kesalahan pada server.',
            'details' => env('APP_DEBUG') ? $e->getMessage() : null, // tampilkan detail hanya di mode debug
        ], 500);
    }
}

public function show($id)
    {
        $report = Report::with([
            'requester',
            'department',
            'status',
            'samplerUser',
            'analystUser',
            'reviewedBy',
            'acknowledgeBy',
        ])->findOrFail($id);

        $report->requested_by_name = $report->requester?->name ?? null;
        $report->sampler_name = $report->samplerUser?->name ?? null;
        $report->analyst_name = $report->analystUser?->name ?? null;
        $report->reviewed_by_name = $report->reviewedBy?->name ?? null;
        $report->acknowledge_by_name = $report->acknowledgeBy?->name ?? null;

        return response()->json($report);
    }

    public function store(Request $request)
{
    try {
        // Pastikan user sudah login
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'location' => 'required|string|max:255',
            'remark' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Buat laporan baru
        $report = Report::create([
            'no_report' => $this->generateReportNumber(),
            'requested_by' => $user->id,
            'department_id' => $user->department_id ?? null,
            'location' => $validated['location'],
            'remark' => $validated['remark'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status_id' => 1,
        ]);

        // Log aktivitas
        ActivityLogger::log(
            'CREATE',
            'reports',
            $report->id,
            null,
            $report->toArray(),
            "{$user->name} created a new report [{$report->id}]"
        );

        // Return response sukses
        return response()->json([
            'message' => 'Report created successfully',
            'report' => $report,
        ], 201);

    } catch (ModelNotFoundException $e) {
        Log::error('Model not found while creating report: ' . $e->getMessage());
        return response()->json([
            'error' => 'Required data not found',
        ], 404);

    } catch (Exception $e) {
        Log::error('Error while creating report: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to create report',
            'details' => env('APP_DEBUG') ? $e->getMessage() : null,
        ], 500);
    }
}

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $this->validate($request, [
            'location' => 'sometimes|required|string|max:100',
            'remark' => 'nullable|string',
            'notes' => 'nullable|string',
            'status_id' => 'nullable|integer|exists:report_status,id',
            'date_sampling' => 'nullable|date',
            'date_analysis' => 'nullable|date',
        ]);

        $report = Report::findOrFail($id);

        $oldData = $report->toArray();

        $report->update($request->only([
            'location',
            'remark',
            'notes',
            'status_id',
            'date_sampling',
            'date_analysis',
        ]));

        ActivityLogger::log(
            'UPDATE',
            'reports',
            $report->id,
            $oldData,
            $report->toArray(),
            "{$user->name} updated report [{$report->id}]"
        );

        return response()->json([
            'message' => 'Report updated successfully',
            'report' => $report,
        ]);
    }

    // public function destroy($id)
    // {
    //     $report = Report::findOrFail($id);
    //     $report->delete();

    //     return response()->json(['message' => 'Report deleted successfully']);
    // }

    public function destroyMultiple(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:laboratory_report,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ids = $request->input('ids');

        $reports = Report::whereIn('id', $ids)->get();
        $oldData = $reports->toArray();

        Report::whereIn('id', $ids)->delete();

        foreach ($oldData as $data) {
            ActivityLogger::log(
                'DELETE',
                'reports',
                $data['id'],
                $data,
                null,
                "{$user->name} deleted report [{$data['id']}]"
            );
        }

        return response()->json([
            'message' => 'Reports deleted successfully',
            'deleted_ids' => $ids,
        ]);
    }

    public function getTakeActionData($id)
    {
        $report = Report::with(['requester', 'department', 'status'])->findOrFail($id);

        $loginUser = auth()->user();
        $subordinates = User::with(['department', 'roles'])
            ->where('superior', $loginUser->id)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'npk' => $user->npk,
                    'job_title' => $user->job_title,
                    'department' => $user->department?->name,
                    'roles' => $user->roles->pluck('name'),
                ];
            });

        return response()->json([
            'report' => $report,
            'subordinates' => $subordinates,
            'login_user' => [
                'id' => $loginUser->id,
                'name' => $loginUser->name,
                'department' => $loginUser->department?->name,
                'roles' => $loginUser->roles->pluck('name'),
            ],
        ]);
    }

    public function takeAction(Request $request, $id)
    {
        $user = Auth::user();

        $this->validate($request, [
            'sampler_id' => 'required|exists:users,id',
            'analyst_id' => 'required|exists:users,id',
            'remark' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $report = Report::findOrFail($id);
        $oldData = $report->toArray();

        // Accept / In Progress
        $report->sampler = $request->sampler_id;
        $report->analyst = $request->analyst_id;
        $report->remark = $request->remark ?? $report->remark;
        $report->notes = $request->notes ?? $report->notes;
        $report->status_id = 2; // In Progress
        $report->save();

        $description = 'User '.Auth::user()->name." accepted report #{$report->id} (In Progress)";

        $description = "{$user->name} accepted report [{$report->id}] (In Progress)";

        ActivityLogger::log(
            'ACCEPT',
            'reports',
            $report->id,
            $oldData,
            $report->toArray(),
            $description
        );

        return response()->json([
            'message' => 'Report accepted successfully',
            'report' => $report->fresh(),
        ]);
    }

    public function finalize($id)
    {
        $user = Auth::user();

        $report = Report::findOrFail($id);

        if (! in_array($report->status_id, [2, 4])) {
            return response()->json([
                'message' => 'Report belum bisa difinalisasi. Pastikan statusnya sedang dianalisis (2) atau revisi (4).',
            ], 400);
        }

        $oldData = $report->toArray();

        $report->status_id = 3; // Finalized
        $report->save();

        ActivityLogger::log(
            'FINALIZE',
            'reports',
            $report->id,
            $oldData,
            $report->toArray(),
            "{$user->name} finalized report [{$report->id}]"
        );

        return response()->json([
            'message' => 'Report berhasil difinalisasi.',
            'report' => $report,
        ]);
    }

    public function review($id)
    {
        $report = Report::with([
            'requester',
            'department',
            'status',
            'samplerUser',
            'analystUser',
            'reviewedBy',
            'acknowledgeBy',
        ])->findOrFail($id);

        $testDetails = \DB::table('test_details')
            ->leftJoin('test_types', 'test_details.test_type_id', '=', 'test_types.id')
            ->leftJoin('methods', 'test_details.method_id', '=', 'methods.id')
            ->leftJoin('standards', 'test_details.standard_id', '=', 'standards.id')
            ->leftJoin('units', 'test_details.unit_id', '=', 'units.id') // 👈 join units
            ->select(
                'test_details.*',
                'test_types.name as test_type',
                'methods.name as method',
                'standards.name as standard',
                'units.name as unit'
            )
            ->where('test_details.report_id', $id)
            ->get();

        $report->requested_by_name = $report->requester?->name ?? null;
        $report->sampler_name = $report->samplerUser?->name ?? null;
        $report->analyst_name = $report->analystUser?->name ?? null;
        $report->reviewed_by_name = $report->reviewedBy?->name ?? null;
        $report->acknowledge_by_name = $report->acknowledgeBy?->name ?? null;

        return response()->json([
            'report' => $report,
            'test_details' => $testDetails,
        ]);
    }

    public function submitReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,revision',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();
        $report = Report::findOrFail($id);

        $oldData = $report->toArray(); // Simpan data lama untuk log

        if ($request->has('comment')) {
            $report->comment = $request->input('comment');
        }

        if ($request->input('action') === 'approve') {
            $report->status_id = 5;
            $report->reviewed_at = Carbon::now();
            $report->reviewed_by = $user->id;

            $actionMessage = "{$user->name} approved report [{$report->id}]";
        } elseif ($request->input('action') === 'revision') {
            $report->status_id = 4;
            $actionMessage = "{$user->name} requested revision for report [{$report->id}]";
        }

        $report->save();

        $report->load('reviewedBy', 'acknowledgeBy');

        $report->reviewed_by_name = $report->reviewedBy?->name ?? null;
        $report->acknowledge_by_name = $report->acknowledgeBy?->name ?? null;

        // --- Activity Log ---
        ActivityLogger::log(
            strtoupper($request->input('action')), // APPROVE atau REVISION
            'reports',
            $report->id,
            $oldData,
            $report->toArray(),
            $actionMessage
        );

        return response()->json([
            'message' => 'Report reviewed successfully',
            'report' => $report,
        ]);
    }

    public function approveReport($id)
    {
        $report = Report::findOrFail($id);
        $user = auth()->user();

        if (! ($user->hasRole('Manager') || $user->hasRole('Admin'))) {
            return response()->json([
                'message' => 'Unauthorized action',
            ], 403);
        }

        $oldData = $report->toArray(); // Simpan data lama untuk log

        $report->status_id = 6;
        $report->acknowledge_by = $user->id;
        $report->acknowledge_at = Carbon::now();
        $report->save();

        $report->load('reviewedBy', 'acknowledgeBy');

        $report->reviewed_by_name = $report->reviewedBy?->name ?? null;
        $report->acknowledge_by_name = $report->acknowledgeBy?->name ?? null;

        // --- Activity Log ---
        ActivityLogger::log(
            'APPROVE',
            'reports',
            $report->id,
            $oldData,
            $report->toArray(),
            "{$user->name} acknowledged/closed report [{$report->id}]"
        );

        return response()->json([
            'message' => 'Report has been closed successfully',
            'report' => $report,
        ]);
    }

    public function generateCoA($id)
    {
        $report = DB::table('laboratory_report as r')
            ->leftJoin('users as u_req', 'r.requested_by', '=', 'u_req.id')
            ->leftJoin('users as u_sam', 'r.sampler', '=', 'u_sam.id')
            ->leftJoin('users as u_ana', 'r.analyst', '=', 'u_ana.id')
            ->leftJoin('users as u_rev', 'r.reviewed_by', '=', 'u_rev.id')
            ->leftJoin('users as u_ack', 'r.acknowledge_by', '=', 'u_ack.id')
            ->leftJoin('departments as d', 'r.department_id', '=', 'd.id')
            ->leftJoin('report_status as s', 'r.status_id', '=', 's.id')
            ->select(
                'r.*',
                'u_req.name as requested_by_name',
                'u_sam.name as sampler_name',
                'u_ana.name as analyst_name',
                'u_rev.name as reviewed_by_name',
                'u_ack.name as acknowledge_by_name',
                'd.name as department_name',
                's.name as status_name'
            )
            ->where('r.id', $id)
            ->first();

        if (! $report) {
            abort(404, 'Report not found');
        }

        $testDetails = DB::table('test_details as td')
            ->leftJoin('test_types as tt', 'td.test_type_id', '=', 'tt.id')
            ->leftJoin('methods as m', 'td.method_id', '=', 'm.id')
            ->leftJoin('standards as st', 'td.standard_id', '=', 'st.id')
            ->leftJoin('units as u', 'td.unit_id', '=', 'u.id') // tambahkan join ke tabel units
            ->select(
                'td.bahan_pengujian',
                'u.name as unit',
                'td.result',
                'td.description',
                'tt.name as test_type',
                'm.name as method',
                'st.name as standard'
            )
            ->where('td.report_id', $id)
            ->get();

        $html = view('coa', compact('report', 'testDetails'))->render();

        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="CoA_'.$report->no_report.'.pdf"');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $report = Report::findOrFail($id);
        $oldData = $report->toArray();

        $report->status_id = 7;
        $report->reason = $request->reason;
        $report->notes = $request->notes ?? $report->notes;
        $report->remark = $request->remark ?? $report->remark;
        $report->save();

        $description = "{$user->name} rejected report [{$report->id}] with reason: {$request->reason}";

        ActivityLogger::log(
            'REJECT',
            'reports',
            $report->id,
            $oldData,
            $report->toArray(),
            $description
        );

        return response()->json([
            'message' => 'Report rejected successfully',
            'report' => $report->fresh(),
        ]);
    }

    public function stats()
    {
        $total = Report::count();

        $requested = Report::where('status_id', 1)->count();
        $inProgress = Report::where('status_id', 2)->count();
        $pendingReview = Report::where('status_id', 3)->count();
        $revision = Report::where('status_id', 4)->count();
        $pendingAcknowledge = Report::where('status_id', 5)->count();
        $closed = Report::where('status_id', 6)->count();
        $rejected = Report::where('status_id', 7)->count();

        return response()->json([
            'total' => $total,
            'requested' => $requested,
            'in_progress' => $inProgress,
            'pending_review' => $pendingReview,
            'revision' => $revision,
            'pending_acknowledge' => $pendingAcknowledge,
            'closed' => $closed,
            'rejected' => $rejected,
        ]);
    }
}
