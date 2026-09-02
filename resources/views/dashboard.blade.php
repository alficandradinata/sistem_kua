{{-- [SISTEM KUA] Dashboard warga (diedit dari Breeze). Lihat PROGRESS.md. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Warga
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Sambutan + CTA --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-gray-900">Selamat datang, <span class="font-semibold">{{ auth()->user()->name }}</span>.</p>
                        <p class="text-sm text-gray-500 mt-1">Ajukan reservasi antrean untuk layanan KUA di sini.</p>
                    </div>
                    <a href="{{ route('reservations.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700">
                        + Buat Reservasi Baru
                    </a>
                </div>
            </div>

            {{-- Reservasi saya --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Reservasi Saya</h3>

                    @if ($reservations->isEmpty())
                        <p class="text-sm text-gray-500">Belum ada reservasi.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b">
                                        <th class="py-2 pr-4">Layanan</th>
                                        <th class="py-2 pr-4">Tanggal</th>
                                        <th class="py-2 pr-4">Jam</th>
                                        <th class="py-2 pr-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reservations as $r)
                                        <tr class="border-b last:border-0 hover:bg-gray-50">
                                            <td class="py-2 pr-4">
                                                <a href="{{ route('reservations.show', $r) }}" class="text-indigo-600 hover:underline">
                                                    {{ $r->service->name }}
                                                </a>
                                            </td>
                                            <td class="py-2 pr-4">{{ $r->formatted_date }}</td>
                                            <td class="py-2 pr-4">{{ $r->formatted_time }}</td>
                                            <td class="py-2 pr-4">
                                                <span class="inline-block px-2 py-0.5 rounded text-xs {{ $r->status_color }}">
                                                    {{ $r->status_label }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($reservations->hasPages())
                            <div class="mt-4">{{ $reservations->links() }}</div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
