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

@push('styles')
    <style>

        .main-content {
            background: var(--bg);
            min-height: 100vh;
            padding: 0 0 80px;
            font-family: var(--font);
        }

        /* ── Top Bar ── */
        .prof-topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 16px 12px;
            background: var(--bg);
            position: sticky; top: 0; z-index: 50;
        }
        .prof-topbar-title {
            display: flex; align-items: center; gap: 8px;
            font-size: 1.18rem; font-weight: 700; color: var(--dark);
        }

        /* ── Summary Card ── */
        .prof-summary-card {
            margin: 0 16px 12px;
            background: var(--card-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: var(--shadow-card);
        }
        .prof-avatar-wrap { position: relative; flex-shrink: 0; }
        .prof-avatar {
            width: 72px; height: 72px; border-radius: 50%;
            background: rgba(255,61,139,.12); color: var(--pink);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; font-weight: 700;
            overflow: hidden; border: 2px solid var(--pink-light);
        }
        .prof-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .prof-avatar-edit {
            position: absolute; bottom: -2px; right: -2px;
            width: 28px; height: 28px; border-radius: 50%;
            background: var(--pink); color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: 2px solid var(--card-bg);
            transition: var(--transition);
        }
        .prof-avatar-edit:hover { background: #e02d7a; }
        .prof-avatar-edit .material-icons-round { font-size: .85rem; }

        .prof-summary-info { flex: 1; min-width: 0; }
        .prof-summary-name { font-size: 1.05rem; font-weight: 700; color: var(--dark); }
        .prof-summary-role { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
        .prof-role-badge {
            display: inline-block; padding: 2px 10px; border-radius: 50px;
            font-size: .68rem; font-weight: 600;
            background: var(--pink-light); color: var(--pink);
        }
        .prof-verified-badge {
            display: inline-flex; align-items: center; gap: 2px;
            font-size: .68rem; font-weight: 600; color: #1A9453;
        }
        .prof-verified-badge .material-icons-round { font-size: .82rem; }
        .prof-summary-meta {
            display: flex; flex-direction: column; gap: 1px;
            margin-top: 6px; font-size: .76rem; color: var(--muted);
        }

        /* ── Avatar Actions Row ── */
        .prof-avatar-actions {
            margin: 0 16px 12px; padding: 10px 14px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; flex-wrap: wrap;
        }
        .prof-avatar-actions-text { font-size: .76rem; color: var(--muted); }
        .prof-avatar-remove-link, .prof-avatar-cancel {
            display: inline-flex; align-items: center; gap: 4px;
            background: #FFF0F0; border: none; border-radius: var(--radius-sm);
            padding: 5px 12px; font-size: .74rem; color: #E53935;
            cursor: pointer; font-family: var(--font); font-weight: 600;
            transition: var(--transition); flex-shrink: 0;
        }
        .prof-avatar-remove-link:hover, .prof-avatar-cancel:hover { background: #FFD6D6; }
        .prof-avatar-remove-link .material-icons-round,
        .prof-avatar-cancel .material-icons-round { font-size: .8rem; }

        /* ── Section Cards ── */
        .prof-card {
            margin: 0 16px 16px;
            background: var(--card-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            overflow: hidden;
        }
        .prof-card-header {
            display: flex; align-items: center; gap: 8px;
            padding: 14px 18px;
            font-size: .88rem; font-weight: 700; color: var(--dark);
            border-bottom: 1px solid var(--border);
        }
        .prof-card-header .material-icons-round { font-size: 1.05rem; color: var(--pink); }
        .prof-card-body { padding: 18px; }

        /* ── Form ── */
        .prof-form-group { margin-bottom: 14px; }
        .prof-form-label {
            display: block; font-size: .8rem; font-weight: 600;
            color: var(--soft-dark); margin-bottom: 5px;
        }
        .prof-form-label .req { color: var(--pink); margin-left: 2px; }
        .prof-form-control {
            width: 100%; padding: 10px 12px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: var(--font); font-size: .84rem; color: var(--dark);
            background: var(--bg); outline: none; transition: var(--transition);
            box-sizing: border-box;
        }
        .prof-form-control:focus {
            border-color: var(--pink); box-shadow: 0 0 0 3px rgba(255,61,139,.1); background: #fff;
        }
        .prof-form-control.is-invalid { border-color: #E53935; }
        .prof-invalid-feedback { color: #E53935; font-size: .74rem; margin-top: 4px; }

        .prof-row { display: flex; gap: 12px; }
        .prof-col { flex: 1; min-width: 0; }

        /* ── Upload Progress ── */
        .prof-upload-progress { margin: -4px 0 14px; }
        .prof-upload-bar {
            height: 4px; border-radius: 99px;
            background: linear-gradient(90deg, var(--pink), #ff8fab);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite; margin-bottom: 4px;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .prof-upload-progress small { font-size: .72rem; color: var(--muted); }

        /* ── Detail Grid ── */
        .prof-detail-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
        }
        .prof-detail-item {
            background: var(--bg); border-radius: var(--radius-sm);
            padding: 10px 12px; border: 1px solid var(--border);
        }
        .prof-detail-label {
            display: block; font-size: .7rem; font-weight: 600;
            color: var(--muted); text-transform: uppercase;
            letter-spacing: .04em; margin-bottom: 3px;
        }
        .prof-detail-value { font-size: .84rem; font-weight: 600; color: var(--dark); }

        /* ── Buttons ── */
        .btn-prof-primary {
            padding: 11px 24px; background: var(--pink); color: #fff;
            border: none; border-radius: var(--radius-md); font-family: var(--font);
            font-size: .86rem; font-weight: 700; cursor: pointer; transition: var(--transition);
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-prof-primary:hover { background: #e02d7a; }
        .btn-prof-primary:disabled { opacity: .6; cursor: not-allowed; }

        /* ── Alerts ── */
        .prof-alert {
            display: flex; align-items: center; gap: 8px;
            margin: 12px 16px; padding: 12px 14px;
            border-radius: var(--radius-md); font-size: .82rem;
        }
        .prof-alert span:not(.material-icons-round) { flex: 1; }
        .prof-alert-success { background: #E8FAF0; color: #1A9453; border: 1px solid #A8E6C4; }
        .prof-alert-error   { background: #FFF0F0; color: #E53935; border: 1px solid #FFBCBC; }
        .prof-alert-close {
            background: none; border: none; cursor: pointer;
            font-size: 1.1rem; color: inherit; padding: 0; line-height: 1;
        }

        /* ── Modal (remove avatar confirm) ── */
        .prof-modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(10,10,30,.55); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
            animation: fadeIn .18s ease; padding: 20px;
        }
        @keyframes fadeIn { from { opacity: 0 } to { opacity: 1 } }

        .prof-delete-modal {
            background: var(--card-bg); border-radius: var(--radius-lg);
            max-width: 320px; width: calc(100% - 32px);
            padding: 28px 20px 20px; text-align: center;
            animation: scaleIn .18s cubic-bezier(.4,0,.2,1);
        }
        @keyframes scaleIn {
            from { transform: scale(.9); opacity: 0; }
            to   { transform: scale(1);  opacity: 1; }
        }
        .prof-delete-icon {
            width: 56px; height: 56px; border-radius: 50%;
            background: #FFF0F0; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 14px; font-size: 1.6rem;
        }
        .prof-delete-modal h6 { font-size: .98rem; font-weight: 700; color: var(--dark); margin: 0 0 6px; }
        .prof-delete-modal p  { font-size: .8rem; color: var(--muted); margin: 0 0 20px; line-height: 1.5; }
        .prof-delete-actions  { display: flex; gap: 10px; justify-content: center; }

        .btn-cancel {
            padding: 9px 20px; background: var(--bg); border: 1.5px solid var(--border);
            border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
            font-weight: 600; color: var(--soft-dark); cursor: pointer; transition: var(--transition);
        }
        .btn-cancel:hover { background: var(--border); }

        .btn-confirm-delete {
            padding: 9px 20px; background: #E53935; border: none;
            border-radius: var(--radius-sm); font-family: var(--font); font-size: .82rem;
            font-weight: 600; color: #fff; cursor: pointer; transition: var(--transition);
            display: flex; align-items: center; gap: 5px;
        }
        .btn-confirm-delete:hover { background: #c62828; }

        /* ── Spinner ── */
        .spinner-sm {
            width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff; border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 480px) {
            .prof-row { flex-direction: column; }
            .prof-detail-grid { grid-template-columns: 1fr; }
            .prof-summary-card { flex-direction: column; text-align: center; }
        }

    </style>
@endpush