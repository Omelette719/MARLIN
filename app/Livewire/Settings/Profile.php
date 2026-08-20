<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pengaturan Profil')]
class Profile extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $nama_panggilan = '';

    public string $no_telepon = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->nama_panggilan = (string) $user->nama_panggilan;
        $this->no_telepon = (string) $user->no_telepon;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules(), $this->profileMessages());

        $validated['nama_panggilan'] = $validated['nama_panggilan'] !== '' ? $validated['nama_panggilan'] : null;
        $validated['no_telepon'] = $validated['no_telepon'] !== '' ? $validated['no_telepon'] : null;

        $user->fill($validated);
        $user->save();

        Flux::toast(variant: 'success', text: __('Profil berhasil diperbarui.'));
    }

    public function render()
    {
        return view('pages::settings.profile');
    }
}
