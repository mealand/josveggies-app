@extends('pdf.layout', ['title' => 'Packing '.$run->code])

@section('content')
    <h1>Packing lists — {{ $run->code }}</h1>
    <p class="muted">{{ count($orders) }} orders</p>

    @foreach ($orders as $row)
        @php $order = $row['order']; @endphp
        <table>
            <thead>
                <tr>
                    <th colspan="2">{{ $order->number }} · {{ $order->user->name }}</th>
                    <th class="right">{{ $order->fulfilment->value === 'dispatch' ? 'DISPATCH · '.$order->dispatchZone?->name : 'PICKUP' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($row['contents'] as $item)
                    <tr>
                        <td style="width: 18px"><span class="box"></span></td>
                        <td>{{ $item['quantity'] }} × {{ $item['name'] }} <span class="muted">({{ $item['line'] }})</span></td>
                        <td class="right muted">{{ $item['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endsection
