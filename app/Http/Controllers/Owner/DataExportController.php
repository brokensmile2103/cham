<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentFarm;
use App\Http\Controllers\Controller;
use App\Services\FarmExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    use ResolvesCurrentFarm;

    public function export(Request $request, FarmExporter $exporter): StreamedResponse
    {
        $farm = $this->currentFarm($request, true);
        $data = $exporter->export($farm);

        $filename = 'cham-'.\Illuminate\Support\Str::slug($farm->name).'-'.now()->format('Ymd').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
