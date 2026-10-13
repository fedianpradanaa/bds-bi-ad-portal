<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Bds\BdsOutputReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OutputReportPrintController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, BdsOutputReportService $service): View
    {
        $user = Auth::user();
        if (! $user || (! $user->isOperator() && ! $user->isSupervisor() && ! $user->isAdmin())) {
            abort(403, 'Akses dibatasi.');
        }

        $date = $request->query('date');
        $supervisorId = $request->query('supervisor_id') ? (int) $request->query('supervisor_id') : null;
        $teamLeaderId = $request->query('team_leader_id') ? (int) $request->query('team_leader_id') : null;

        $availableDates = $service->getAvailableDates();
        $selectedDate = $date ?? (! empty($availableDates) ? $availableDates[0] : date('Y-m-d'));

        $reportData = $service->getReportData($selectedDate, 'single');

        $supervisor = $supervisorId ? User::find($supervisorId) : User::where('role', 'supervisor')->first();
        $teamLeader = $teamLeaderId ? User::find($teamLeaderId) : (User::where('is_team_leader', true)->first() ?? User::where('role', 'operator')->first());

        $supervisorName = $supervisor?->name ?? '-';
        $supervisorNip = $supervisor?->nip ?? $supervisor?->username ?? '-';
        $teamLeaderName = $teamLeader?->name ?? '-';
        $teamLeaderNip = $teamLeader?->nip ?? $teamLeader?->username ?? '-';

        $rawPrinter = $user->name ?? $supervisorName;
        $cleanPrinter = trim(preg_replace('/\bBDS\b/i', '', $rawPrinter));
        $printer = ! empty($cleanPrinter) ? $cleanPrinter : $rawPrinter;

        return view('bds.output-report-print', [
            'reportData' => $reportData,
            'hasData' => ! empty($reportData),
            'selectedDate' => $selectedDate,
            'availableDates' => $availableDates,
            'supervisorName' => $supervisorName,
            'supervisorNip' => $supervisorNip,
            'teamLeaderName' => $teamLeaderName,
            'teamLeaderNip' => $teamLeaderNip,
            'printer' => $printer,
            'printTimestamp' => Carbon::now()->format('d.m.Y H:i'),
        ]);
    }
}
