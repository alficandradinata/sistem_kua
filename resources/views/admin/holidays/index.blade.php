{{-- [SISTEM KUA] Master data hari libur. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Master Data — Hari Libur</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <p class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Tanggal yang terdaftar &amp; aktif di sini akan diblokir dari form reservasi warga.
            </p>

            {{-- Tambah --}}
            <form method="POST" action="{{ route('admin.holidays.store') }}"
                  class="grid gap-4 rounded-lg bg-white p-5 shadow-sm sm:grid-cols-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Tanggal</label>
                    <input type="date" name="holiday_date" value="{{ old('holiday_date') }}" required
                           class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-500">Keterangan</label>
                    <input type="text" name="description" value="{{ old('description') }}" required maxlength="150"
                           placeholder="mis. Hari Raya Idul Fitri"
                           class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <input type="hidden" name="is_active" value="1">
                <div class="flex items-end">
                    <x-primary-button>+ Tambah</x-primary-button>
                </div>
            </form>

            {{-- Filter tahun --}}
            <form method="GET" class="flex items-end gap-3 rounded-lg bg-white p-5 shadow-sm">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Tahun</label>
                    <select name="year" class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Tampilkan</button>
            </form>

            {{-- Daftar --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Keterangan</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($holidays as $holiday)
                            <tr class="hover:bg-gray-50">
                                <form method="POST" action="{{ route('admin.holidays.update', $holiday) }}" id="hol-{{ $holiday->id }}">
                                    @csrf @method('PUT')
                                </form>
                                <td class="px-5 py-3">
                                    <input form="hol-{{ $holiday->id }}" type="date" name="holiday_date"
                                           value="{{ $holiday->holiday_date->toDateString() }}"
                                           class="rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <div class="mt-0.5 text-xs text-gray-400">{{ $holiday->formatted_date }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    <input form="hol-{{ $holiday->id }}" type="text" name="description" maxlength="150"
                                           value="{{ $holiday->description }}"
                                           class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </td>
                                <td class="px-5 py-3">
                                    <label class="flex items-center gap-2 text-xs text-gray-600">
                                        <input form="hol-{{ $holiday->id }}" type="checkbox" name="is_active" value="1"
                                               @checked($holiday->is_active)
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Aktif
                                    </label>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button form="hol-{{ $holiday->id }}"
                                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                            Simpan
                                        </button>
                                        <form method="POST" action="{{ route('admin.holidays.destroy', $holiday) }}"
                                              onsubmit="return confirm('Hapus hari libur ini?')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-500">Belum ada hari libur di tahun {{ $year }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
