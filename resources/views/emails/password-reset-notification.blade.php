<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #D39305 0%, #b37d04 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0; font-size: 24px;">Password Reset</h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p style="font-size: 16px;">Hello <strong>{{ $user->name }}</strong>,</p>
        
        <p>Your password for <strong>{{ $businessName }}</strong> has been reset by an administrator.</p>
        
        <div style="background: #fff; border: 2px solid #D39305; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center;">
            <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Your new temporary password:</p>
            <p style="font-size: 24px; font-weight: bold; color: #D39305; margin: 0; letter-spacing: 2px;">{{ $newPassword }}</p>
        </div>
        
        <p><strong>Important:</strong> Please log in and change your password immediately for security.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginUrl }}" style="background: #D39305; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Login Now</a>
        </div>
        
        <p style="color: #666; font-size: 14px;">If you did not expect this password reset, please contact your administrator immediately.</p>
        
        <hr style="border: none; border-top: 1px solid #ddd; margin: 25px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center; margin: 0;">
            This email was sent by {{ config('app.name') }}.<br>
            Please do not reply to this email.
        </p>
    </div>
</body>
</html>
