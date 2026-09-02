{{-- [SISTEM KUA] Detail reservasi warga. Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Reservasi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-lg">{{ $reservation->service->name }}</h3>
                    <span class="inline-block px-2 py-0.5 rounded text-xs {{ $reservation->status_color }}">
                        {{ $reservation->status_label }}
                    </span>
                </div>

                <dl class="grid grid-cols-3 gap-y-3 text-sm">
                    <dt class="text-gray-500">Tanggal</dt>
                    <dd class="col-span-2">{{ $reservation->full_date }}</dd>
                    <dt class="text-gray-500">Jam</dt>
                    <dd class="col-span-2">{{ $reservation->formatted_time }}</dd>
                    <dt class="text-gray-500">Biaya</dt>
                    <dd class="col-span-2">{{ $reservation->service->formatted_fee }}</dd>
                    @if ($reservation->notes)
                        <dt class="text-gray-500">Catatan</dt>
                        <dd class="col-span-2">{{ $reservation->notes }}</dd>
                    @endif
                    @if ($reservation->queueDetail)
                        <dt class="text-gray-500">No. Antrean</dt>
                        <dd class="col-span-2 font-semibold">{{ $reservation->queueDetail->queue_number }}</dd>
                    @endif
                </dl>

                <div class="flex items-center gap-3 pt-2 border-t">
                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Kembali</a>
                    @if ($reservation->canBeCancelled())
                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}"
                            onsubmit="return confirm('Batalkan reservasi ini?')" class="ms-auto">
                            @csrf
                            @method('PATCH')
                            <button class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">
                                Batalkan
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
