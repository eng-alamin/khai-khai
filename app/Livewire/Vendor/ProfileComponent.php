<?php

namespace App\Livewire\Vendor;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileComponent extends Component
{
    use WithFileUploads;

    // ── Profile Info ──────────────────────────────────────
    public string  $name          = '';
    public string  $phone         = '';
    public string  $email         = '';
    public         $avatar        = null;
    public ?string $existingAvatar = null;

    // ── Password Change ───────────────────────────────────
    public string $current_password      = '';
    public string $new_password           = '';
    public string $new_password_confirmation = '';

    // ── UI State ──────────────────────────────────────────
    public bool $confirmRemoveAvatar = false;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name           = $user->name;
        $this->phone          = $user->phone ?? '';
        $this->email          = $user->email ?? '';
        $this->existingAvatar = $user->avatar;
    }

    // ── Validation ────────────────────────────────────────
    protected function rules(): array
    {
        $user = Auth::user();

        return [
            'name'   => 'required|string|max:100',
            'phone'  => ['nullable', 'string', 'max:15', Rule::unique('users', 'phone')->ignore($user->id)],
            'email'  => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'  => 'Please enter your name.',
            'phone.unique'   => 'This phone number is already in use.',
            'email.unique'   => 'This email is already in use.',
            'avatar.image'   => 'Avatar must be an image.',
            'avatar.max'     => 'Avatar must not exceed 2 MB.',
        ];
    }

    // ── Update Profile Info ────────────────────────────────
    public function updateProfile(): void
    {
        $this->validate([
            'name'   => $this->rules()['name'],
            'phone'  => $this->rules()['phone'],
            'email'  => $this->rules()['email'],
            'avatar' => $this->rules()['avatar'],
        ]);

        $user = Auth::user();

        $avatarPath = $this->existingAvatar;
        if ($this->avatar) {
            if ($this->existingAvatar && str_starts_with($this->existingAvatar, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $this->existingAvatar));
            }
            $stored     = $this->avatar->store('avatars', 'public');
            $avatarPath = Storage::url($stored);
        }

        $user->update([
            'name'   => $this->name,
            'phone'  => $this->phone ?: null,
            'email'  => $this->email ?: null,
            'avatar' => $avatarPath,
        ]);

        activity()->causedBy($user)->performedOn($user)->log('Updated own profile');

        $this->existingAvatar = $avatarPath;
        $this->avatar = null;

        session()->flash('success', 'Profile updated successfully!');
    }

    // ── Remove Avatar ──────────────────────────────────────
    public function confirmAvatarRemove(): void
    {
        $this->confirmRemoveAvatar = true;
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($this->existingAvatar && str_starts_with($this->existingAvatar, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $this->existingAvatar));
        }

        $user->update(['avatar' => null]);
        $this->existingAvatar = null;
        $this->confirmRemoveAvatar = false;

        session()->flash('success', 'Avatar removed.');
    }

    // ── Update Password ─────────────────────────────────────
    public function updatePassword(): void
    {
        $this->validate([
            'current_password'         => 'required|string',
            'new_password'             => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'Please enter your current password.',
            'new_password.required'     => 'Please enter a new password.',
            'new_password.min'          => 'New password must be at least 6 characters.',
            'new_password.confirmed'    => 'Password confirmation does not match.',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Your current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->new_password)]);

        activity()->causedBy($user)->performedOn($user)->log('Changed own password');

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->resetValidation();

        session()->flash('success', 'Password changed successfully!');
    }

    public function render()
    {
        return view('livewire.vendor.profile-component', [
            'user' => Auth::user(),
        ])->layout('layouts.vendor', [
            'title'           => 'My Profile | KhaiKhai',
            'breadcrumbTitle' => 'Profile',
        ]);
    }
}