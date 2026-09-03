{{-- [SISTEM KUA] Master data layanan. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">Master Data — Layanan</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <div class="flex justify-end">
                <a href="{{ route('admin.services.create') }}"
                   class="rounded-md bg-kua-600 px-4 py-2 text-sm font-semibold text-white hover:bg-kua-700">
                    + Tambah Layanan
                </a>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 text-sm">
                        <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                            <tr>
                                <th class="px-5 py-3">Nama</th>
                                <th class="px-5 py-3">Durasi</th>
                                <th class="px-5 py-3">Biaya</th>
                                <th class="px-5 py-3 text-center">Slot</th>
                                <th class="px-5 py-3 text-center">Reservasi</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @forelse ($services as $service)
                                <tr class="hover:bg-stone-50">
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-stone-900">{{ $service->name }}</div>
                                        <div class="max-w-md truncate text-xs text-stone-500">{{ $service->description }}</div>
                                    </td>
                                    <td class="px-5 py-3">{{ $service->duration_label }}</td>
                                    <td class="px-5 py-3">{{ $service->formatted_fee }}</td>
                                    <td class="px-5 py-3 text-center">{{ $service->slots_count }}</td>
                                    <td class="px-5 py-3 text-center">{{ $service->reservations_count }}</td>
                                    <td class="px-5 py-3">
                                        @if ($service->is_active)
                                            <x-status-badge color="bg-kua-100 text-kua-800" label="Aktif" />
                                        @else
                                            <x-status-badge color="bg-stone-100 text-stone-800" label="Nonaktif" />
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.services.edit', $service) }}"
                                               class="rounded-md border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50">
                                                Ubah
                                            </a>
                                            <form method="POST" action="{{ route('admin.services.destroy', $service) }}"
                                                  onsubmit="return confirm('Hapus layanan {{ $service->name }}?')">
                                                @csrf @method('DELETE')
                                                <button @disabled($service->reservations_count > 0)
                                                    title="{{ $service->reservations_count > 0 ? 'Sudah dipakai reservasi — nonaktifkan saja' : '' }}"
                                                    class="rounded-md px-3 py-1.5 text-xs font-semibold
                                                        {{ $service->reservations_count > 0
                                                            ? 'cursor-not-allowed border border-stone-200 text-stone-300'
                                                            : 'border border-rose-300 text-rose-700 hover:bg-rose-50' }}">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-5 py-10 text-center text-stone-500">Belum ada layanan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($services->hasPages())
                    <div class="border-t border-stone-100 px-5 py-3">{{ $services->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
