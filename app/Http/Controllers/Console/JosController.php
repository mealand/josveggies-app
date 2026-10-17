<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Run;
use App\Services\ProcurementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Jos Ops market-day tools. Lists are downloadable as PDFs the night before so the team
 * can work offline if the market has poor connectivity.
 */
class JosController extends Controller
{
    public function __construct(private ProcurementService $procurement) {}

    public function procurement(Run $run): View
    {
        return view('console.jos.procurement', [
            'run' => $run,
            'items' => $this->procurement->procurementList($run),
            'orderCount' => $run->paidOrders()->count(),
        ]);
    }

    public function procurementPdf(Run $run): Response
    {
        return $this->pdf('pdf.procurement', [
            'run' => $run,
            'items' => $this->procurement->procurementList($run),
        ], "procurement-{$run->code}.pdf");
    }

    public function packing(Run $run): View
    {
        return view('console.jos.packing', [
            'run' => $run,
            'orders' => $this->packingRows($run),
        ]);
    }

    public function packingPdf(Run $run): Response
    {
        return $this->pdf('pdf.packing', ['run' => $run, 'orders' => $this->packingRows($run)], "packing-{$run->code}.pdf");
    }

    public function labelsPdf(Run $run): Response
    {
        return $this->pdf('pdf.labels', ['run' => $run, 'orders' => $this->procurement->paidOrders($run)], "labels-{$run->code}.pdf");
    }

    public function substitutions(Run $run): View
    {
        return view('console.jos.substitutions', ['run' => $run]);
    }

    /**
     * @return list<array{order: Order, contents: list<array<string, mixed>>}>
     */
    private function packingRows(Run $run): array
    {
        return $this->procurement->paidOrders($run)
            ->map(fn ($order): array => ['order' => $order, 'contents' => $this->procurement->packedContents($order)])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pdf(string $view, array $data, string $filename): Response
    {
        activity('documents')->causedBy(auth()->user())->withProperties(['file' => $filename])->log('pdf.downloaded');

        return Pdf::loadView($view, $data)->setPaper('a4')->download($filename);
    }
}
