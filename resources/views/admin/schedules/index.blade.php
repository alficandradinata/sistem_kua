{{-- [SISTEM KUA] Jam operasional KUA (7 hari, ubah sekaligus). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-stone-800">Master Data — Jam Operasional</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-admin-tabs />
            <x-alert />

            <p class="rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                Hari yang tidak dicentang berarti KUA tutup — warga tidak bisa memilih tanggal pada hari itu.
            </p>

            <form method="POST" action="{{ route('admin.schedules.update') }}"
                  class="overflow-hidden rounded-lg bg-white shadow-sm">
                @csrf @method('PUT')

                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-5 py-3">Hari</th>
                            <th class="px-5 py-3">Buka</th>
                            <th class="px-5 py-3">Jam buka</th>
                            <th class="px-5 py-3">Jam tutup</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($days as $index => $dayName)
                            @php $schedule = $schedules[$index]; @endphp
                            <tr x-data="{ aktif: {{ old("days.$index.is_active", $schedule->is_active) ? 'true' : 'false' }} }">
                                <td class="px-5 py-3 font-medium text-stone-900">{{ $dayName }}</td>
                                <td class="px-5 py-3">
                                    <input type="hidden" name="days[{{ $index }}][is_active]" :value="aktif ? 1 : 0">
                                    <input type="checkbox" x-model="aktif"
                                           class="rounded border-stone-300 text-kua-600 focus:ring-kua-500">
                                </td>
                                <td class="px-5 py-3">
                                    <input type="time" name="days[{{ $index }}][open_time]"
                                           value="{{ old("days.$index.open_time", $schedule->open_time ? substr($schedule->open_time, 0, 5) : '08:00') }}"
                                           :disabled="!aktif"
                                           class="rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500 disabled:bg-stone-100 disabled:text-stone-400">
                                    <x-input-error :messages="$errors->get(\"days.$index.open_time\")" class="mt-1" />
                                </td>
                                <td class="px-5 py-3">
                                    <input type="time" name="days[{{ $index }}][close_time]"
                                           value="{{ old("days.$index.close_time", $schedule->close_time ? substr($schedule->close_time, 0, 5) : '15:00') }}"
                                           :disabled="!aktif"
                                           class="rounded-md border-stone-300 text-sm focus:border-kua-500 focus:ring-kua-500 disabled:bg-stone-100 disabled:text-stone-400">
                                    <x-input-error :messages="$errors->get(\"days.$index.close_time\")" class="mt-1" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-t border-stone-100 px-5 py-4">
                    <x-primary-button>Simpan Jam Operasional</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
