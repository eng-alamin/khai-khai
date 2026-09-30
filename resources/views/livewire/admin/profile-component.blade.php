<div>

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="material-icons-round">check_circle</i>
            <span>{{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="material-icons-round">error</i>
            <span>{{ session('error') }}</span>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
    @endif

    <div class="main-content">

        {{-- ── Top Bar ── --}}
        <div class="topbar">
            <div class="topbar-title">
                <span class="title-emoji">👤</span>
                My Profile
            </div>
        </div>

        {{-- ── Profile Summary Card ── --}}
        <div class="profile-summary">
            <div class="avatar-wrap">
                <div class="avatar">
                    @if($existingAvatar && !$avatar)
                        <img src="{{ $existingAvatar }}" alt="{{ $user->name }}">
                    @elseif($avatar)
                        <img src="{{ $avatar->temporaryUrl() }}" alt="Preview">
                    @else
                        <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    @endif
                </div>
                <label class="avatar-edit" title="Change avatar">
                    <i class="material-icons-round">photo_camera</i>
                    <input type="file" wire:model="avatar" accept="image/*" hidden>
                </label>
            </div>
            <div class="profile-summary-info">
                <div class="profile-summary-name">{{ $user->name }}</div>
                <div class="profile-summary-role">
                    <span class="sec-pill">{{ ucfirst($user->role) }}</span>
                    @if($user->is_verified)
                        <span class="verified-badge">
                            <i class="material-icons-round">verified</i> Verified
                        </span>
                    @endif
                </div>
                <div class="profile-summary-meta">
                    @if($user->email) <span>{{ $user->email }}</span> @endif
                    @if($user->phone) <span>{{ $user->phone }}</span> @endif
                </div>
            </div>
        </div>

        @if($avatar)
            <div class="avatar-actions">
                <span class="avatar-actions-text">New avatar selected — click "Save Changes" below to apply.</span>
                <button type="button" class="mini-danger-btn" wire:click="$set('avatar', null)">
                    <i class="material-icons-round">close</i> Cancel
                </button>
            </div>
        @elseif($existingAvatar)
            <div class="avatar-actions">
                <span class="avatar-actions-text">Want to remove your current avatar?</span>
                <button type="button" class="mini-danger-btn" wire:click="confirmAvatarRemove">
                    <i class="material-icons-round">delete</i> Remove Avatar
                </button>
            </div>
        @endif

        @error('avatar')
            <div class="form-group"><div class="invalid-feedback" style="margin:-6px 16px 10px;">{{ $message }}</div></div>
        @enderror

        {{-- ── Edit Profile Card ── --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="material-icons-round">badge</i>
                Profile Information
            </div>
            <div class="info-card-body">

                <div class="form-group">
                    <label class="form-label">Full Name <span class="req">*</span></label>
                    <input type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        wire:model.defer="name"
                        placeholder="Your full name">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="text"
                                class="form-control @error('phone') is-invalid @enderror"
                                wire:model.defer="phone"
                                placeholder="01XXXXXXXXX">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                wire:model.defer="email"
                                placeholder="you@example.com">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div wire:loading wire:target="avatar" class="upload-progress">
                    <div class="upload-bar"></div>
                    <small>Uploading avatar...</small>
                </div>

                <button class="save-btn"
                    wire:click="updateProfile"
                    wire:loading.attr="disabled">
                    <span wire:loading wire:target="updateProfile" class="spinner-sm"></span>
                    Save Changes
                </button>

            </div>
        </div>

        {{-- ── Change Password Card ── --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="material-icons-round">lock</i>
                Change Password
            </div>
            <div class="info-card-body">

                <div class="form-group">
                    <label class="form-label">Current Password <span class="req">*</span></label>
                    <input type="password"
                        class="form-control @error('current_password') is-invalid @enderror"
                        wire:model.defer="current_password"
                        placeholder="Enter current password">
                    @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label class="form-label">New Password <span class="req">*</span></label>
                            <input type="password"
                                class="form-control @error('new_password') is-invalid @enderror"
                                wire:model.defer="new_password"
                                placeholder="Min 6 characters">
                            @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label class="form-label">Confirm New Password <span class="req">*</span></label>
                            <input type="password"
                                class="form-control"
                                wire:model.defer="new_password_confirmation"
                                placeholder="Re-type new password">
                        </div>
                    </div>
                </div>

                <button class="save-btn"
                    wire:click="updatePassword"
                    wire:loading.attr="disabled">
                    <span wire:loading wire:target="updatePassword" class="spinner-sm"></span>
                    Update Password
                </button>

            </div>
        </div>

        {{-- ── Account Meta Card ── --}}
        <div class="info-card">
            <div class="info-card-header">
                <i class="material-icons-round">info</i>
                Account Details
            </div>
            <div class="info-card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Role</span>
                        <span class="detail-value">{{ ucfirst($user->role) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Account Status</span>
                        <span class="detail-value">
                            @if($user->is_active)
                                <span style="color:#1A9453">Active</span>
                            @else
                                <span style="color:#E53935">Inactive</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Verified</span>
                        <span class="detail-value">{{ $user->is_verified ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Points</span>
                        <span class="detail-value">{{ number_format($user->points) }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Last Login</span>
                        <span class="detail-value">{{ $user->last_login_at?->format('d M Y, h:i A') ?? '—' }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Member Since</span>
                        <span class="detail-value">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /main-content --}}


    {{-- ══════════════════════════════════════
         Remove Avatar Confirmation Modal
    ══════════════════════════════════════ --}}
    @if($confirmRemoveAvatar)
        <div class="jara-modal-backdrop">
            <div class="jara-delete-modal">
                <div class="delete-icon">⚠️</div>
                <h6>Remove Avatar?</h6>
                <p>Your profile picture will be removed.<br>You can upload a new one anytime.</p>
                <div class="delete-actions">
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
