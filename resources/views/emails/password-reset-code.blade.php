<!DOCTYPE html>
<html>
<body style="margin:0;padding:24px;background:#f7f7f9;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:12px;padding:28px;">
        <h2 style="margin:0 0 12px;font-size:20px;">Reset your password</h2>
        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
            Hi {{ $name }}, use this code in the app to choose a new password:
        </p>
        <p style="margin:0 0 16px;text-align:center;font-size:34px;font-weight:700;letter-spacing:8px;">
            {{ $code }}
        </p>
        <p style="margin:0;font-size:13px;line-height:1.6;color:#666;">
            The code works for {{ $minutes }} minutes. If you did not ask for it, you can ignore this
            email &mdash; your password stays the same. Never share this code with anyone.
        </p>
    </div>
</body>
</html>
