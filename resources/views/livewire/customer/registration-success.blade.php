{{-- resources/views/vendor/registration-success.blade.php --}}
<x-layouts.guest>
<div class="container py-5" style="max-width:520px;">
    <div class="text-center py-5">
        <div style="font-size:4rem; margin-bottom:1rem;">🎉</div>
        <h1 style="font-size:1.5rem; font-weight:700; color:#1A1A2E; margin-bottom:.5rem;">
            আবেদন সফলভাবে জমা হয়েছে!
        </h1>
        <p style="color:#6C757D; font-size:.9rem; margin-bottom:2rem;">
            KhaiKhai টিম আপনার তথ্য যাচাই করবে।<br>
            সাধারণত ২৪ ঘণ্টার মধ্যে SMS এবং ইমেইলে জানানো হবে।
        </p>
        <div style="background:#E8F7F2; border:1px solid #A7E3CF; border-radius:14px; padding:1.5rem; margin-bottom:2rem;">
            <div style="font-size:1.5rem; margin-bottom:.5rem;">📱</div>
            <p style="font-size:.85rem; color:#0F6E56; margin:0;">
                পরবর্তী পদক্ষেপের জন্য আপনার মোবাইলে নজর রাখুন।
            </p>
        </div>
        <a href="{{ route('home') }}"
            style="background:linear-gradient(135deg,#FF6B35,#FF8C42);color:#fff;border:none;border-radius:12px;padding:.75rem 2rem;font-weight:700;font-size:.95rem;text-decoration:none;display:inline-block;">
            হোমপেইজে যান
        </a>
    </div>
</div>
</x-layouts.guest>