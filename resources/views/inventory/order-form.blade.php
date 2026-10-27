@extends('layouts.app')
@section('title', 'New supply order')
@section('heading', 'New Supply Order')

@section('content')
<form method="POST" action="{{ route('orders.store') }}" id="order" class="max-w-4xl space-y-4">
    @csrf
    <section class="bg-white rounded-xl border border-slate-200 p-5 text-sm grid md:grid-cols-2 gap-4">
        <label>Deliver to
            <select name="location_id" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
            </select>
        </label>
        <label>Notes <input name="notes" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2"></label>
    </section>

    <section class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500 bg-slate-50">
                <tr><th class="px-4 py-2">Item</th><th class="px-4 py-2 text-right">Price</th><th class="px-4 py-2 text-right">In stock</th><th class="px-4 py-2 w-32">Quantity</th><th class="px-4 py-2 text-right">Line total</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="(line, idx) in lines" :key="line.id">
                    <td class="px-4 py-2">
                        @{{ line.name }}
                        <input type="hidden" :name="`lines[${idx}][item_id]`" :value="line.id">
                    </td>
                    <td class="px-4 py-2 text-right">$@{{ fmt(line.price) }}</td>
                    <td class="px-4 py-2 text-right">@{{ line.stock }}</td>
                    <td class="px-4 py-2"><input type="number" min="0" :max="line.stock" :name="`lines[${idx}][quantity]`" v-model.number="line.qty" class="w-full rounded-md border border-slate-300 px-2 py-1"></td>
                    <td class="px-4 py-2 text-right">$@{{ fmt(line.qty * line.price) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200 font-semibold"><td colspan="4" class="px-4 py-3 text-right">Order total</td><td class="px-4 py-3 text-right">$@{{ fmt(total) }}</td></tr>
            </tfoot>
        </table>
    </section>

    <button class="bg-indigo-600 text-white rounded-md px-6 py-2 text-sm" :disabled="total <= 0">Submit order</button>
</form>
@endsection

@push('scripts')
<script>
    const ITEMS = {{ Illuminate\Support\Js::from(
    $items->map(fn ($i) => [
        'id' => $i->id,
        'name' => $i->name,
        'price' => (float) $i->unit_price,
        'stock' => $i->stock,
        'qty' => 0,
    ])->values()
) }};
    Vue.createApp({
        data: () => ({ lines: ITEMS }),
        computed: { total() { return this.lines.reduce((s, l) => s + (l.qty || 0) * l.price, 0); } },
        methods: { fmt: (n) => Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
    }).mount('#order');
</script>
@endpush
