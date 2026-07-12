<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>New Vendor Registration</title>
</head>
<body style="margin:0; padding:0; background:#F8F9FA; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FA; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #E8E8F0;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#3d0a6e,#e91e8c); padding:24px 32px;">
                            <h1 style="color:#ffffff; font-size:18px; margin:0;">🍽️ KhaiKhai — New Vendor Registration</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;">
                            <p style="font-size:14px; color:#1A1A2E; margin:0 0 16px;">
                                A new restaurant has registered and is waiting for approval.
                            </p>

                            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="font-size:14px; color:#1A1A2E;">
                                <tr>
                                    <td style="color:#6C757D; width:160px;">Restaurant Name</td>
                                    <td style="font-weight:bold;">{{ $restaurant->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6C757D;">Category</td>
                                    <td>{{ $restaurant->category ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6C757D;">City</td>
                                    <td>{{ $restaurant->city ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6C757D;">Address</td>
                                    <td>{{ $restaurant->address ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6C757D;">Restaurant Phone</td>
                                    <td>{{ $restaurant->phone ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6C757D;">Owner Name</td>
                                    <td>{{ $vendorUser->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6C757D;">Owner Email</td>
                                    <td>{{ $vendorUser->email ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#6C757D;">Owner Phone</td>
                                    <td>{{ $vendorUser->phone ?? '—' }}</td>
                                </tr>
                            </table>

                            <p style="font-size:13px; color:#6C757D; margin:24px 0 0;">
                                Please log in to the Admin Panel to review and approve this restaurant.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#FAFAFA; padding:16px 32px; font-size:12px; color:#ADB5BD;">
                            This is an automated notification from KhaiKhai.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>