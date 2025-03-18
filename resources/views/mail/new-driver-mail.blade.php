@component('mail::message')
# Account Created Successfully!

Hello, {{ $createUser['name'] }}, your driver account has been successfully created. Kindly download the mobile app and change your password:

- **Email**: {{ $createUser['email'] }}
- **Temporary Password**: 0000000000

@component('mail::panel')
**Important**: Please change your password after logging in for the first time.
@endcomponent

@component('mail::subcopy')
If you have any questions, please contact us at support@smileslogistics.com.
@endcomponent

Regards,<br>
{{ config('app.name') }}
@endcomponent