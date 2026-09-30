{{-- resources/views/livewire/rider/profile-component.blade.php --}}
{{-- Styles: resources/css/blade.css (shared classes, Bootstrap 5 required) --}}
<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="prof-alert prof-alert-success">
            <span class="material-icons-round">check_circle</span>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="prof-alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="prof-alert prof-alert-error">
            <span class="material-icons-round">error</span>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="prof-alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="prof-topbar">
            <div class="prof-topbar-title">
                <span class="title-emoji">👤</span>
                My Profile
            </div>
        </div>

        {{-- ── Profile Summary Card ── --}}
        <div class="prof-summary-card">
            <div class="prof-avatar-wrap">
                <div class="prof-avatar">
                    @if($existingAvatar && !$avatar)
                        <img src="{{ $existingAvatar }}" alt="{{ $user->name }}">
                    @elseif($avatar)
                        <img src="{{ $avatar->temporaryUrl() }}" alt="Preview">
                    @else
                        <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    @endif
                </div>
                <label class="prof-avatar-edit" title="Change avatar">
                    <span class="material-icons-round">photo_camera</span>
                    <input type="file" wire:model="avatar" accept="image/*" hidden>
                </label>
            </div>
            <div class="prof-summary-info">
                <div class="prof-summary-name">{{ $user->name }}</div>
                <div class="prof-summary-role">
                    <span class="prof-role-badge">{{ ucfirst($user->role) }}</span>
                    @if($user->is_verified)
                        <span class="prof-verified-badge">
                            <span class="material-icons-round">verified</span> Verified
                        </span>
                    @endif
                </div>
                <div class="prof-summary-meta">
                    @if($user->email) <span>{{ $user->email }}</span> @endif
                    @if($user->phone) <span>{{ $user->phone }}</span> @endif
                </div>
            </div>
        </div>

        @if($avatar)
            <div class="prof-avatar-actions">
                <span class="prof-avatar-actions-text">New avatar selected — click "Save Changes" below to apply.</span>
                <button type="button" class="prof-avatar-cancel" wire:click="$set('avatar', null)">
                    <span class="material-icons-round">close</span> Cancel
                </button>
            </div>
        @elseif($existingAvatar)
            <div class="prof-avatar-actions">
                <span class="prof-avatar-actions-text">Want to remove your current avatar?</span>
                <button type="button" class="prof-avatar-remove-link" wire:click="confirmAvatarRemove">
                    <span class="material-icons-round">delete</span> Remove Avatar
                </button>
            </div>
        @endif

        @error('avatar')
            <div class="prof-form-group"><div class="prof-invalid-feedback" style="margin:-6px 16px 10px;">{{ $message }}</div></div>
        @enderror

        {{-- ── Edit Profile Card ── --}}
        <div class="prof-card">
            <div class="prof-card-header">
                <span class="material-icons-round">badge</span>
                Profile Information
            </div>
            <div class="prof-card-body">

                <div class="prof-form-group">
                    <label class="prof-form-label">Full Name <span class="req">*</span></label>
                    <input type="text"
                        class="prof-form-control @error('name') is-invalid @enderror"
                        wire:model.defer="name"
                        placeholder="Your full name">
                    @error('name') <div class="prof-invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="prof-row">
                    <div class="prof-col">
                        <div class="prof-form-group">
                            <label class="prof-form-label">Phone</label>
                            <input type="text"
                                class="prof-form-control @error('phone') is-invalid @enderror"
                                wire:model.defer="phone"
                                placeholder="01XXXXXXXXX">
                            @error('phone') <div class="prof-invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="prof-col">
                        <div class="prof-form-group">
                            <label class="prof-form-label">Email</label>
                            <input type="email"
                                class="prof-form-control @error('email') is-invalid @enderror"
                                wire:model.defer="email"
                                placeholder="you@example.com">
                            @error('email') <div class="prof-invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div wire:loading wire:target="avatar" class="prof-upload-progress">
                    <div class="prof-upload-bar"></div>
                    <small>Uploading avatar...</small>
                </div>

                <button class="btn-prof-primary"
                    wire:click="updateProfile"
                    wire:loading.attr="disabled">
                    <span wire:loading wire:target="updateProfile" class="spinner-sm"></span>
                    Save Changes
                </button>

            </div>
        </div>

        {{-- ── Change Password Card ── --}}
        <div class="prof-card">
            <div class="prof-card-header">
                <span class="material-icons-round">lock</span>
                Change Password
            </div>
            <div class="prof-card-body">

                <div class="prof-form-group">
                    <label class="prof-form-label">Current Password <span class="req">*</span></label>
                    <input type="password"
                        class="prof-form-control @error('current_password') is-invalid @enderror"
                        wire:model.defer="current_password"
                        placeholder="Enter current password">
                    @error('current_password') <div class="prof-invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="prof-row">
                    <div class="prof-col">
                        <div class="prof-form-group">
                            <label class="prof-form-label">New Password <span class="req">*</span></label>
                            <input type="password"
                                class="prof-form-control @error('new_password') is-invalid @enderror"
                                wire:model.defer="new_password"
                                placeholder="Min 6 characters">
                            @error('new_password') <div class="prof-invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="prof-col">
                        <div class="prof-form-group">
                            <label class="prof-form-label">Confirm New Password <span class="req">*</span></label>
                            <input type="password"
                                class="prof-form-control"
                                wire:model.defer="new_password_confirmation"
                                placeholder="Re-type new password">
                        </div>
                    </div>
                </div>

                <button class="btn-prof-primary"
                    wire:click="updatePassword"
                    wire:loading.attr="disabled">
                    <span wire:loading wire:target="updatePassword" class="spinner-sm"></span>
                    Update Password
                </button>

            </div>
        </div>

        {{-- ── Account Meta Card ── --}}
        <div class="prof-card">
            <div class="prof-card-header">
                <span class="material-icons-round">info</span>
                Account Details
            </div>
            <div class="prof-card-body">
                <div class="prof-detail-grid">
                    <div class="prof-detail-item">
                        <span class="prof-detail-label">Role</span>
                        <span class="prof-detail-value">{{ ucfirst($user->role) }}</span>
                    </div>
                    <div class="prof-detail-item">
                        <span class="prof-detail-label">Account Status</span>
                        <span class="prof-detail-value">
                            @if($user->is_active)
                                <span style="color:#1A9453">Active</span>
                            @else
                                <span style="color:#E53935">Inactive</span>
                            @endif
                        </span>
                    </div>
                    <div class="prof-detail-item">
                        <span class="prof-detail-label">Verified</span>
                        <span class="prof-detail-value">{{ $user->is_verified ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="prof-detail-item">
                        <span class="prof-detail-label">Points</span>
                        <span class="prof-detail-value">{{ number_format($user->points) }}</span>
                    </div>
                    <div class="prof-detail-item">
                        <span class="prof-detail-label">Last Login</span>
                        <span class="prof-detail-value">{{ $user->last_login_at?->format('d M Y, h:i A') ?? '—' }}</span>
                    </div>
                    <div class="prof-detail-item">
                        <span class="prof-detail-label">Member Since</span>
                        <span class="prof-detail-value">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Remove Avatar Confirmation Modal
    ══════════════════════════════════════ --}}
    @if($confirmRemoveAvatar)
        <div class="prof-modal-backdrop">
            <div class="prof-delete-modal">
                <div class="prof-delete-icon">⚠️</div>
                <h6>Remove Avatar?</h6>
                <p>Your profile picture will be removed.<br>You can upload a new one anytime.</p>
                <div class="prof-delete-actions">
                    <button class="btn-cancel"
                        wire:click="$set('confirmRemoveAvatar', false)">Cancel</button>
                    <button class="btn-confirm-delete"
                        wire:click="removeAvatar">
                        <span wire:loading wire:target="removeAvatar" class="spinner-sm"></span>
                        Remove
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>

