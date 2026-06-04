<div>
    <div class="row g-3">
        <div class="col-md-6">
        <div class="card">
            <div style="text-align:center;padding:20px 0 16px;">
            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--pink),var(--accent));display:flex;align-items:center;justify-content:center;font-size:32px;color:#fff;font-weight:700;margin:0 auto 12px;">${cfg.avatar}</div>
            <div style="font-size:20px;font-weight:800;">${cfg.name}</div>
            <div style="color:var(--text-3);font-size:13px;">${cfg.role}</div>
            <div style="margin-top:12px;"><span class="badge-kk badge-green"><i class="fa fa-check-circle"></i> ভেরিফাইড</span></div>
            </div>
            <div style="border-top:1px solid var(--border);padding-top:16px;">
            <div class="form-group"><label class="form-label-kk">ফোন নম্বর</label><input class="form-control-kk" value="01712-345678"></div>
            <div class="form-group"><label class="form-label-kk">ইমেইল</label><input class="form-control-kk" value="user@example.com"></div>
            <div class="form-group"><label class="form-label-kk">ডিফল্ট ঠিকানা</label><input class="form-control-kk" value="Gazipur, Dhaka-1700"></div>
            <button class="btn-kk btn-primary-kk" style="width:100%;justify-content:center;" onclick="showToast('প্রোফাইল আপডেট ✅','success')"><i class="fa fa-save"></i> সেভ করুন</button>
            </div>
        </div>
        </div>
        <div class="col-md-6">
        <div class="d-flex flex-column gap-3">
            <div class="stat-card sc-pink"><div class="stat-icon"><i class="fa fa-shopping-bag"></i></div><div class="stat-info"><div class="num">47</div><div class="label">মোট অর্ডার</div></div></div>
            <div class="stat-card sc-green"><div class="stat-icon"><i class="fa fa-star"></i></div><div class="stat-info"><div class="num">4.8★</div><div class="label">গড় রেটিং</div></div></div>
            <div class="stat-card sc-orange"><div class="stat-icon"><i class="fa fa-coins"></i></div><div class="stat-info"><div class="num">৳120</div><div class="label">KK পয়েন্ট</div></div></div>
            <div class="card">
            <div class="card-title mb-3">দ্রুত অ্যাকশন</div>
            <div class="d-flex flex-column gap-2">
                <button class="btn-kk btn-ghost-kk" style="justify-content:flex-start;" onclick="showToast('পাসওয়ার্ড পরিবর্তন','info')"><i class="fa fa-lock"></i> পাসওয়ার্ড পরিবর্তন</button>
                <button class="btn-kk btn-ghost-kk" style="justify-content:flex-start;" onclick="showToast('নোটিফিকেশন সেটিংস','info')"><i class="fa fa-bell"></i> নোটিফিকেশন</button>
                <button class="btn-kk btn-ghost-kk" style="justify-content:flex-start;color:var(--danger);" onclick="showPortal()"><i class="fa fa-sign-out-alt"></i> লগআউট</button>
            </div>
            </div>
        </div>
        </div>
    </div>
</div>
