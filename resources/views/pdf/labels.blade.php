@extends('pdf.layout', ['title' => 'Labels '.$run->code])

@section('content')
    <table style="margin-top: 0">
        @foreach ($orders->chunk(2) as $pair)
            <tr>
                @foreach ($pair as $order)
                    <td style="width: 50%; height: 150px; border: 2px dashed #b9dec2; padding: 14px;">
                        <div style="font-size: 9px; color: #6b7a70;">JosVeggies · {{ $run->code }}</div>
                        <div style="font-size: 24px; font-weight: bold; margin-top: 4px;">{{ $order->number }}</div>
                        <div style="font-size: 14px; margin-top: 4px;">{{ $order->user->name }}</div>
                        <div style="margin-top: 6px;">
                            @foreach ($order->lines as $line)
                                {{ $line->quantity }} × {{ $line->name }}@if (! $loop->last), @endif
                            @endforeach
                        </div>
                        <div style="margin-top: 10px; display: inline-block; padding: 4px 10px; font-weight: bold; color: #fff; background: {{ $order->fulfilment->value === 'dispatch' ? '#e4572e' : '#2e6b30' }};">
                            {{ $order->fulfilment->value === 'dispatch' ? 'DISPATCH · '.strtoupper((string) $order->dispatchZone?->name) : 'HUB PICKUP' }}
                        </div>
                    </td>
                @endforeach
                @if ($pair->count() === 1)<td></td>@endif
            </tr>
        @endforeach
    </table>
@endsection
