<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * [SISTEM KUA] Kelola akun & peran. Satu-satunya cara membuat akun petugas/admin,
 * karena registrasi publik selalu menghasilkan warga. Lihat PROGRESS.md.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->string('role')->toString() ?: null;
        $search = $request->string('q')->toString() ?: null;

        return view('admin.users.index', [
            'users' => User::query()
                ->when($role, fn ($q, $r) => $q->role($r))
                ->when($search, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
                ->withCount('reservations')
                ->orderBy('role')->orderBy('name')
                ->paginate(15)->withQueryString(),
            'roles' => User::ROLES,
            'role' => $role,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => User::ROLE_PETUGAS])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        // email_verified_at sengaja TIDAK ada di $fillable, jadi harus di-set eksplisit.
        // Akun internal dibuat admin, tidak perlu lewat verifikasi email.
        User::create($data)->forceFill(['email_verified_at' => now()])->save();

        return to_route('admin.users.index')->with('status', 'Akun berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Admin tidak boleh menurunkan perannya sendiri dan mengunci diri dari panel.
        if ($user->is($request->user()) && $data['role'] !== User::ROLE_ADMIN) {
            return back()->withErrors(['role' => 'Anda tidak bisa mengubah peran akun Anda sendiri.']);
        }

        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return to_route('admin.users.index')->with('status', 'Akun diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak bisa menghapus akun Anda sendiri.']);
        }

        if ($user->reservations()->exists()) {
            return back()->withErrors([
                'user' => 'Akun ini punya riwayat reservasi. Menghapusnya akan ikut menghapus riwayat tersebut. '
                    .'Ubah perannya saja bila hanya ingin mencabut akses.',
            ]);
        }

        $user->delete();

        return back()->with('status', 'Akun dihapus.');
    }
}
