<?php

namespace App\Http\Controllers;

use App\Services\Bds\BreakdownReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class BreakdownReportPrintController extends Controller
{
    public function __invoke(Request $request, BreakdownReportService $service): View
    {
        $startDate = $request->query('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->query('end_date', Carbon::today()->format('Y-m-d'));

        $lang = $request->query('lang', 'en');

        $reports = $service->getReportsByDateRange($startDate, $endDate);

        // Ambil nama user yang sedang login (Filament Guard)
        $currentUser = Auth::user();
        $printedByName = $currentUser ? $currentUser->name : 'Administrator';

        // Ubah logo PNG lokal menjadi Base64 Data URI agar selalu terbawa saat Print/PDF
        $logoPath = public_path('images/bank_indonesia_logo.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        return view('bds.breakdown-report-print', [
            'reports'        => $reports,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
            'printTimestamp' => Carbon::now()->format('d.m.Y H:i'),
            'logoBase64'     => $logoBase64,
            'printedByName'  => $printedByName,
            'lang'           => $lang,
        ]);
    }
}