<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\VatChallan;
use App\Models\VatChallanEvent;
use App\Services\Vat\Challan\ChallanBuyer;
use App\Services\Vat\Challan\ChallanIssuer;
use App\Services\Vat\Challan\ChallanPdf;
use App\Services\Vat\Challan\ChallanRefused;
use App\Services\Vat\Challan\ChallanRegister;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Mushak 6.3 tax challans: the sales that can carry one, the challans already issued, and the printed copy of each.
 * Viewing needs the VAT view permission. Issuing, voiding and recording a buyer BIN need the VAT edit permission.
 */
class VatChallanController extends Controller
{
    public function __construct(
        protected ChallanRegister $register,
        protected ChallanIssuer $issuer,
        protected ChallanBuyer $buyer,
        protected ChallanPdf $pdf,
    ) {
        $this->middleware('permission:setting-list|setting-edit', ['only' => ['index', 'show', 'pdf', 'export']]);
        $this->middleware('permission:setting-edit', ['only' => ['issue', 'issueSelected', 'setBuyerBin', 'void']]);
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        $candidates = $this->register->candidates($from, $to);
        $issuedByOrder = VatChallan::query()
            ->whereIn('issued_order_id', collect($candidates)->map(fn ($row) => $row['order']->id))
            ->get()
            ->keyBy('issued_order_id');

        return view('backEnd.vat.challans.index', [
            'from' => $from,
            'to' => $to,
            'candidates' => $candidates,
            'issuedByOrder' => $issuedByOrder,
            'issued' => $this->register->issued($from, $to),
        ]);
    }

    public function issue(Request $request, Order $order)
    {
        try {
            $challan = $this->issuer->issue($order, $this->actorId(), $request->ip());
        } catch (ChallanRefused $e) {
            Toastr::error($e->getMessage(), 'Not issued');

            return back();
        }

        Toastr::success("Challan {$challan->challan_no} issued.", 'Success');

        return redirect()->route('admin.vat.challans.show', $challan);
    }

    /** Issues a challan for each selected sale. Each sale is its own transaction, so one refusal does not stop the rest. */
    public function issueSelected(Request $request)
    {
        $request->validate(['order_ids' => 'required|array|min:1', 'order_ids.*' => 'integer|exists:orders,id']);

        $issued = [];
        $refused = [];
        foreach (Order::whereIn('id', $request->input('order_ids'))->orderBy('id')->get() as $order) {
            try {
                $issued[] = $this->issuer->issue($order, $this->actorId(), $request->ip())->challan_no;
            } catch (ChallanRefused $e) {
                $refused[] = $order->invoice_id.': '.$e->getMessage();
            }
        }

        if ($issued !== []) {
            Toastr::success(count($issued).' challan(s) issued: '.implode(', ', $issued), 'Success');
        }
        if ($refused !== []) {
            Toastr::warning(count($refused).' sale(s) not issued. '.implode(' ', $refused), 'Not issued');
        }
        if ($issued === [] && $refused === []) {
            Toastr::info('No sales were selected.', 'Nothing to do');
        }

        return back();
    }

    public function setBuyerBin(Request $request, Order $order)
    {
        $request->validate(['buyer_bin' => 'required|string|max:20']);

        try {
            $this->buyer->setBin($order, (string) $request->input('buyer_bin'), $this->actorId(), $request->ip());
        } catch (\InvalidArgumentException | \DomainException $e) {
            Toastr::error($e->getMessage(), 'Not saved');

            return back();
        }

        Toastr::success("Buyer BIN saved for {$order->invoice_id}.", 'Success');

        return back();
    }

    public function show(VatChallan $challan)
    {
        $challan->load(['lines', 'events']);
        $order = Order::find($challan->order_id);

        return view('backEnd.vat.challans.show', [
            'challan' => $challan,
            'order' => $order,
            'events' => VatChallanEvent::where('vat_challan_id', $challan->id)->orWhere('order_id', $challan->order_id)->orderBy('id')->get(),
        ]);
    }

    public function pdf(VatChallan $challan): Response
    {
        try {
            $bytes = $this->pdf->stored($challan);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }

        VatChallanEvent::record('pdf_downloaded', $challan->id, $challan->order_id, $this->actorId(), request()->ip(), [
            'challan_no' => $challan->challan_no,
            'pdf_sha256' => $challan->pdf_sha256,
        ]);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$challan->challan_no.'.pdf"',
        ]);
    }

    public function void(Request $request, VatChallan $challan)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->issuer->void($challan, (string) $request->input('reason'), $this->actorId(), $request->ip());
        } catch (\DomainException | \InvalidArgumentException $e) {
            Toastr::error($e->getMessage(), 'Not voided');

            return back();
        }

        Toastr::success("Challan {$challan->challan_no} voided. The number is kept on record.", 'Success');

        return redirect()->route('admin.vat.challans.show', $challan);
    }

    /** Issued challans in the period, as CSV. The printed copy is the PDF; this list is for the register. */
    public function export(Request $request): Response
    {
        [$from, $to] = $this->period($request);

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Challan No', 'Status', 'Issue Date', 'Invoice No', 'Buyer', 'Buyer BIN', 'Taxable Value', 'Output VAT', 'Total incl. VAT', 'Void Reason']);
        foreach ($this->register->issued($from, $to) as $challan) {
            fputcsv($handle, [
                $challan->challan_no,
                $challan->status,
                $challan->issue_date->toDateString(),
                $challan->invoice_no,
                $challan->buyer_name,
                $challan->buyer_bin,
                number_format((float) $challan->total_value, 2, '.', ''),
                number_format((float) $challan->total_vat, 2, '.', ''),
                number_format((float) $challan->total_with_tax, 2, '.', ''),
                $challan->void_reason,
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $name = sprintf('vat-challans-%s-to-%s.csv', $from->toDateString(), $to->toDateString());

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    private function actorId(): ?int
    {
        return auth('admin')->id() ?? auth()->id();
    }

    /** The same period rule as the VAT reports: the current month by default, and an inverted range is swapped. */
    private function period(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        return $from->gt($to) ? [$to, $from] : [$from, $to];
    }
}
