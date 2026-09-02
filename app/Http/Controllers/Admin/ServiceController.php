<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Master data layanan. Lihat PROGRESS.md.
 */
class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', [
            'services' => Service::withCount(['slots', 'reservations'])->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service(['is_active' => true, 'duration' => 30, 'fee' => 0])]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return to_route('admin.services.index')->with('status', 'Layanan berhasil ditambahkan.');
    }

    public function edit(Service $service): View
    {
        return view('admin.services.form', compact('service'));
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return to_route('admin.services.index')->with('status', 'Layanan berhasil diperbarui.');
    }

    /**
     * Layanan yang sudah dipakai reservasi TIDAK boleh dihapus — riwayat warga
     * harus tetap utuh. Arahkan admin untuk menonaktifkannya saja.
     */
    public function destroy(Service $service): RedirectResponse
    {
        if ($service->reservations()->exists()) {
            return back()->withErrors([
                'service' => 'Layanan ini sudah dipakai pada reservasi sehingga tidak bisa dihapus. '
                    .'Nonaktifkan saja agar tidak muncul di form reservasi baru.',
            ]);
        }

        $service->delete();

        return to_route('admin.services.index')->with('status', 'Layanan dihapus.');
    }
}
