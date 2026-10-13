<?php

namespace App\Http\Controllers;

use App\Services\Bds\BdsReportDataService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BdsCertificatePrintController extends Controller
{
    public function __invoke(Request $request, BdsReportDataService $reportDataService): View
    {
        $date = $request->query('date', '2026-09-29');
        $batchId = $request->query('batch');
        $supervisorId = $request->query('supervisor_id') ? (int) $request->query('supervisor_id') : null;
        $teamLeaderId = $request->query('team_leader_id') ? (int) $request->query('team_leader_id') : null;
        
        // Default aktif ke report1 jika tidak dipilih
        $activeReport = $request->query('report', 'report1');
        $dateFilterMode = $request->query('date_filter_mode', 'single');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Mengambil data terstruktur 8 report dari service database
        $data = $reportDataService->getReportData($date, $batchId, $supervisorId, $dateFilterMode, $startDate, $endDate, $teamLeaderId);

        $data['activeReport'] = $activeReport;
        $data['printedBy'] = Auth::user()?->name ?? $data['supervisorName'];
        $data['printTimestamp'] = Carbon::now()->format('d.m.Y H:i');

        return view('bds.certificate-print', $data);
    }
}