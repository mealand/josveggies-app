@extends('pdf.layout', ['title' => 'Procurement '.$run->code])

@section('content')
    <h1>Procurement list — {{ $run->code }}</h1>
    <p class="muted">CONFIDENTIAL · Market day <span class="confidential">{{ $run->market_day->format('l j F Y') }}</span> · {{ $run->slots_confirmed }} boxes confirmed</p>

    <table>
        <thead><tr><th style="width: 18px"></th><th>Item</th><th class="right">Quantity</th><th>Pack</th><th class="right">Est. cost</th><th>Actual paid</th></tr></thead>
        <tbody>
            @foreach ($items as $row)
                <tr>
                    <td><span class="box"></span></td>
                    <td><strong>{{ $row['name'] }}</strong></td>
                    <td class="right"><strong>{{ $row['quantity'] }}</strong></td>
                    <td>{{ $row['pack'] ?? '—' }}</td>
                    <td class="right">{{ \App\Support\Money::format($row['estimated_cost_kobo']) }}</td>
                    <td style="border-bottom: 1px solid #14231a;">&nbsp;</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
