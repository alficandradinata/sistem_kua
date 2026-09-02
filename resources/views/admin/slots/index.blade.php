{{-- [SISTEM KUA] Master data slot & kuota antrean. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Master Data — Slot Antrean</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            {{-- Tambah slot --}}
            <form method="POST" action="{{ route('admin.slots.store') }}"
                  class="grid gap-4 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-5">
                @csrf
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-500">Layanan</label>
                    <select name="service_id" required
                            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">— pilih layanan —</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected(old('service_id', $serviceId) == $service->id)>
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Jam mulai</label>
                    <input type="time" name="slot_start_time" value="{{ old('slot_start_time', '08:00') }}" required
                           class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Durasi (mnt)</label>
                    <input type="number" name="slot_duration" min="5" max="240" step="5"
                           value="{{ old('slot_duration', 60) }}" required
                           class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Kuota/hari</label>
                    <input type="number" name="quota_per_day" min="0" max="500"
                           value="{{ old('quota_per_day', 5) }}" required
                           class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <input type="hidden" name="is_active" value="1">
                <div class="sm:col-span-5">
                    <x-primary-button>+ Tambah Slot</x-primary-button>
                </div>
            </form>

            {{-- Filter --}}
            <form method="GET" class="flex items-end gap-3 rounded-lg bg-white p-5 shadow-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Saring per layanan</label>
                    <select name="service_id" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua layanan</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected($serviceId == $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Terapkan</button>
                <a href="{{ route('admin.slots.index') }}" class="py-2 text-sm text-gray-500 hover:text-gray-800">Reset</a>
            </form>

            {{-- Daftar --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Layanan</th>
                                <th class="px-5 py-3">Jam</th>
                                <th class="px-5 py-3">Durasi</th>
                                <th class="px-5 py-3">Kuota/hari</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($slots as $slot)
                                <tr class="hover:bg-gray-50">
                                    <form method="POST" action="{{ route('admin.slots.update', $slot) }}" id="slot-{{ $slot->id }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="service_id" value="{{ $slot->service_id }}">
                                    </form>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $slot->service->name }}</td>
                                    <td class="px-5 py-3">
                                        <input form="slot-{{ $slot->id }}" type="time" name="slot_start_time"
                                               value="{{ substr($slot->slot_start_time, 0, 5) }}"
                                               class="w-28 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <div class="mt-0.5 text-xs text-gray-400">s/d {{ substr($slot->slot_end_time, 0, 5) }}</div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <input form="slot-{{ $slot->id }}" type="number" name="slot_duration" min="5" max="240" step="5"
                                               value="{{ $slot->slot_duration }}"
                                               class="w-20 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-5 py-3">
                                        <input form="slot-{{ $slot->id }}" type="number" name="quota_per_day" min="0" max="500"
                                               value="{{ $slot->quota_per_day }}"
                                               class="w-20 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-5 py-3">
                                        <label class="flex items-center gap-2 text-xs text-gray-600">
                                            <input form="slot-{{ $slot->id }}" type="checkbox" name="is_active" value="1"
                                                   @checked($slot->is_active)
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            Aktif
                                        </label>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button form="slot-{{ $slot->id }}"
                                                    class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Simpan
                                            </button>
                                            <form method="POST" action="{{ route('admin.slots.destroy', $slot) }}"
                                                  onsubmit="return confirm('Hapus slot ini?')">
                                                @csrf @method('DELETE')
                                                <button class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500">Belum ada slot.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
