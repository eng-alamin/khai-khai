<div>
<style>
    :root {
        --pink: #e91e8c;
        --kk-dark: #1A1A2E;
        --kk-border: #E8E8F0;
        --kk-green: #1D9E75;
        --kk-green-light: #E8F7F2;
    }
    body { font-family: 'Hind Siliguri', 'Segoe UI', sans-serif; }

    .success-header {
        background: linear-gradient(135deg, #3d0a6e 0%, #7c1e8c 50%, var(--pink) 100%);
        padding: 3rem 0 5rem;
        position: relative;
        overflow: hidden;
        text-align: center;
    }
    .success-header::after {
        content: '';
        position: absolute;
        bottom: -1px; left: 0; right: 0;
        height: 40px;
        background: #FFF8F5;
        clip-path: ellipse(55% 100% at 50% 100%);
    }
    .success-icon {
        width: 90px; height: 90px;
        border-radius: 50%;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 2.6rem;
        margin: 0 auto 1rem;
        box-shadow: 0 8px 24px rgba(0,0,0,.18);
    }
    .success-header h1 { color: #fff; font-weight: 700; font-size: 1.5rem; margin-bottom: .4rem; }
    .success-header p { color: rgba(255,255,255,.85); font-size: .92rem; }

    .success-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 8px 40px rgba(26,26,46,.08);
        border: 1px solid var(--kk-border);
        padding: 2rem;
        margin-top: -3.5rem;
        position: relative;
        z-index: 2;
    }

    .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        background: #FFF8E1; color: #856404;
        border: 1px solid #FFE082;
        padding: .4rem 1rem; border-radius: 99px;
        font-size: .82rem; font-weight: 600;
        margin-bottom: 1.2rem;
    }

    .detail-row {
        display: flex; justify-content: space-between;
        font-size: .88rem; padding: .55rem 0;
        border-bottom: 1px solid var(--kk-border);
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .d-label { color: #6C757D; }
    .detail-row .d-value { font-weight: 600; color: var(--kk-dark); }

    .steps-list { margin-top: 1.5rem; }
    .steps-list .step-row {
        display: flex; align-items: flex-start; gap: 12px;
        margin-bottom: 1rem;
    }
    .steps-list .step-num {
        width: 28px; height: 28px; border-radius: 50%;
        background: var(--kk-green-light); color: var(--kk-green);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .8rem; flex-shrink: 0;
    }
    .steps-list .step-text { font-size: .85rem; color: var(--kk-dark); }
    .steps-list .step-text small { color: #6C757D; display: block; margin-top: 2px; }

    .btn-kk-primary {
        background: linear-gradient(135deg, #7c1e8c 0%, var(--pink) 100%);
        color: #fff; border: none; border-radius: 12px;
        padding: .75rem 1.8rem; font-weight: 700; font-size: .95rem;
        box-shadow: 0 4px 15px rgba(233,30,140,.3);
        text-decoration: none; display: inline-block;
    }
    .btn-kk-primary:hover { color: #fff; transform: translateY(-1px); }
</style>

<div class="success-header">
    <div class="container">
        <div class="success-icon">✅</div>
        <h1>Registration Submitted!</h1>
        <p>Thank you for registering your restaurant with KhaiKhai</p>
    </div>
</div>

<div class="container pb-5" style="max-width: 640px;">
    <div class="success-card">

        <span class="status-pill">⏳ Pending Approval</span>

        @if($restaurant)
            <div class="mb-4">
                <div class="detail-row">
                    <span class="d-label">Restaurant Name</span>
                    <span class="d-value">{{ $restaurant->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="d-label">Category</span>
                    <span class="d-value">{{ $restaurant->category }}</span>
                </div>
                <div class="detail-row">
                    <span class="d-label">City</span>
                    <span class="d-value">{{ $restaurant->city }}</span>
                </div>
                @if($restaurant->owner)
                <div class="detail-row">
                    <span class="d-label">Owner Email</span>
                    <span class="d-value">{{ $restaurant->owner->email }}</span>
                </div>
                @endif
            </div>
        @endif

        <p style="font-size:.9rem; color:#495057;">
            Your restaurant is now waiting for review by the KhaiKhai team. This usually takes up to
            <strong>24 hours</strong>. You will be notified by email once your restaurant is approved and ready to receive orders.
        </p>

        <div class="steps-list">
            <div class="step-row">
                <span class="step-num">1</span>
                <span class="step-text">
                    Our team reviews your restaurant details
                    <small>We check your information for accuracy and completeness.</small>
                </span>
            </div>
            <div class="step-row">
                <span class="step-num">2</span>
                <span class="step-text">
                    Approval decision
                    <small>You'll receive an email once your restaurant is approved.</small>
                </span>
            </div>
            <div class="step-row">
                <span class="step-num">3</span>
                <span class="step-text">
                    Start receiving orders
                    <small>Log in to your vendor dashboard to add menu items and go live.</small>
                </span>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" class="btn-kk-primary">Go to Login</a>
        </div>
    </div>
</div>

</div>