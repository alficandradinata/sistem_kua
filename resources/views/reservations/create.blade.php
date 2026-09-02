{{-- [SISTEM KUA] Form buat reservasi warga. Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buat Reservasi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-4">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Langkah 1: pilih layanan & tanggal --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">1. Pilih layanan &amp; tanggal</h3>
                <form method="GET" action="{{ route('reservations.create') }}" class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">Layanan</label>
                        <select name="service_id" required
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— pilih —</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}" @selected($serviceId == $service->id)>
                                    {{ $service->name }} ({{ $service->formatted_fee }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Tanggal</label>
                        <input type="date" name="reservation_date" value="{{ $date }}"
                            min="{{ now()->addDay()->toDateString() }}" required
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="sm:col-span-3">
                        <button class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-semibold hover:bg-gray-700">
                            Lihat slot tersedia
                        </button>
                    </div>
                </form>
                @if ($dateError)
                    <p class="mt-3 text-sm text-red-600">{{ $dateError }}</p>
                @endif
            </div>

            {{-- Langkah 2: pilih slot --}}
            @if ($slots->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">2. Pilih slot &amp; konfirmasi</h3>
                    <form method="POST" action="{{ route('reservations.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="service_id" value="{{ $serviceId }}">
                        <input type="hidden" name="reservation_date" value="{{ $date }}">

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($slots as $slot)
                                @php $penuh = $slot->sisa_kuota <= 0; @endphp
                                <label class="flex items-center justify-between border rounded-lg px-4 py-3 text-sm
                                    {{ $penuh ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'cursor-pointer hover:border-indigo-400' }}">
                                    <span class="flex items-center gap-3">
                                        <input type="radio" name="slot_id" value="{{ $slot->id }}"
                                            @disabled($penuh) @checked(old('slot_id') == $slot->id)
                                            class="text-indigo-600 focus:ring-indigo-500">
                                        <span class="font-medium">{{ $slot->time_range }}</span>
                                    </span>
                                    <span class="text-gray-500">
                                        {{ $penuh ? 'Penuh' : 'Sisa '.$slot->sisa_kuota }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Catatan (opsional)</label>
                            <textarea name="notes" rows="2" maxlength="500"
                                class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                        </div>

                        <button class="px-5 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700">
                            Kirim Reservasi
                        </button>
                    </form>
                </div>
            @elseif ($serviceId && $date && ! $dateError)
                <p class="text-sm text-gray-500">Tidak ada slot untuk layanan/tanggal ini.</p>
            @endif

        </div>
    </div>
</x-app-layout>
