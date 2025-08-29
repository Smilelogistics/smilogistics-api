<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
  <meta charset="UTF-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Driver Account Created</title>
  <style>
    /* Generic email-safe resets */
    body, table, td, p { margin:0; padding:0; }
    img { border:0; outline:none; text-decoration:none; display:block; }
    table { border-collapse:collapse; }
    a { text-decoration:none; }
    /* Layout helpers */
    .bg-page { background:#F5F7FB; }
    .container { width:100%; max-width:640px; }
    /* Card */
    .card {
      background:#FFFFFF;
      border-radius:24px;
      box-shadow: 0 2px 6px rgba(16,24,40,0.04);
    }
    /* Typography */
    .h1 {
      font-family: Arial, Helvetica, sans-serif;
      font-weight:700;
      font-size:24px;
      line-height:32px;
      color:#111827; /* near-black */
      text-align:center;
    }
    .body {
      font-family: Arial, Helvetica, sans-serif;
      font-weight:400;
      font-size:16px;
      line-height:26px;
      color:#6B7280; /* gray-500/600 */
      text-align:center;
    }
    .small {
      font-size:12px;
      line-height:18px;
      color:#6B7280;
      text-align:center;
      font-family: Arial, Helvetica, sans-serif;
    }
    /* Blue panel */
    .panel {
      background:#1D4ED8; /* vivid brand blue */
      color:#FFFFFF;
      border-radius:16px;
      text-align:center;
      font-family: Arial, Helvetica, sans-serif;
    }
    .panel p {
      margin:0;
      font-weight:700;
      font-size:18px;
      line-height:26px;
    }
    /* Important note */
    .note-wrap { padding-left:14px; } /* space before the black bar */
    .note {
      background:#FEF3C7; /* soft amber */
      border-radius:6px;
      padding:14px 16px;
      text-align:center;
      font-family: Arial, Helvetica, sans-serif;
    }
    .note-title {
      font-weight:700;
      font-size:13px;
      color:#111827;
      margin:0 0 4px 0;
    }
    .note-text {
      font-size:13px;
      line-height:20px;
      color:#6B7280;
      margin:0;
    }
    .black-bar {
      width:6px;
      background:#000000;
      border-radius:3px;
    }
    /* Footer "Need Help?" */
    .help {
      font-family: Arial, Helvetica, sans-serif;
      font-size:22px;
      line-height:28px;
      color:#0F172A; /* slate-900 */
      font-weight:800; /* bold like the mock */
      margin:0;
      text-align:center;
    }
    .help-call {
      font-family: Arial, Helvetica, sans-serif;
      font-size:16px;
      line-height:24px;
      color:#6B7280;
      margin:0;
      text-align:center;
    }
    /* Spacing utilities (email-safe as table cells) */
    .sp-8 { height:8px; line-height:8px; }
    .sp-12 { height:12px; line-height:12px; }
    .sp-16 { height:16px; line-height:16px; }
    .sp-20 { height:20px; line-height:20px; }
    .sp-24 { height:24px; line-height:24px; }
    .sp-28 { height:28px; line-height:28px; }
    .sp-32 { height:32px; line-height:32px; }
    .sp-40 { height:40px; line-height:40px; }
    .sp-48 { height:48px; line-height:48px; }
    .sp-56 { height:56px; line-height:56px; }
    /* Mobile width */
    @media only screen and (max-width: 640px) {
      .container { max-width:100% !important; }
      .note-wrap { padding-left:10px !important; }
    }
  </style>
</head>
<body class="bg-page" style="background:#F5F7FB;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="bg-page" style="background:#F5F7FB;">
    <tr><td align="center" style="padding:24px 16px;">
      <!-- Card -->
      <table role="presentation" width="640" cellpadding="0" cellspacing="0" class="container card" style="width:100%; max-width:640px; background:#FFFFFF; border-radius:24px;">
        <tr><td style="padding:32px 28px 28px 28px;">
          <!-- Logo -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td align="center">
                <img src="" width="140" alt="SMILES Logistics Group" style="width:140px; height:auto;">
              </td>
            </tr>
          </table>

          <div class="sp-24"></div>

          <!-- Title -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr><td class="h1" style="font-family:Arial,Helvetica,sans-serif; font-weight:700; font-size:24px; line-height:32px; color:#111827; text-align:center;">
              Customer Account Created Successfully!
            </td></tr>
          </table>

          <div class="sp-16"></div>

          <!-- Intro -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr><td class="body" style="font-family:Arial,Helvetica,sans-serif; font-size:16px; line-height:26px; color:#6B7280; text-align:center;">
              Hello, your customer account has been successfully created.<br>
              Kindly download the mobile app and change your<br>
              password:
            </td></tr>
          </table>

          <div class="sp-24"></div>

          <!-- Blue Panel -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="panel" style="background:#143D96; color:#FFFFFF; border-radius:16px;">
            <tr><td style="padding:18px 16px;">
              <p style="margin:0; font-family:sans-serif,Arial,Helvetica; font-weight:600; font-size:18px; line-height:24px; text-align:center">Email: {{$user['email']}}</p>
              <div class="sp-8"></div>
              <p style="margin:0; font-family:sans-serif,Arial,Helvetica; font-weight:600; font-size:18px; line-height:24px; text-align:center">Temporary Password: 123456789</p>
            </td></tr>
          </table>

          <div class="sp-24"></div>

          <!-- Important Note with black bar -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="note-wrap" style="padding-left:14px;">
            <tr>
              <td class="black-bar" width="6" style="width:6px; background:#000000; border-radius:3px;">&nbsp;</td>
              <td width="12" style="width:12px;">&nbsp;</td>
              <td class="note" style="background:#FEF3C7; border-radius:6px; padding:14px 16px;">
                <p class="note-title" style="font-family:Arial,Helvetica,sans-serif; font-weight:700; font-size:13px; color:#111827; margin:0 0 4px 0;">Important:</p>
                <p class="note-text" style="font-family:Arial,Helvetica,sans-serif; font-size:13px; line-height:20px; color:#6B7280; margin:0;">
                  Please change your password after logging in for the first time.
                </p>
              </td>
            </tr>
          </table>

          <div class="sp-28"></div>

          <!-- Regards -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr><td class="body" style="font-family:Arial,Helvetica,sans-serif; font-size:16px; line-height:26px; color:#6B7280; text-align:center;">
              Warm Regards,<br>
              <span style="color:#111827;">{{ config('app.name') }}</span>
            </td></tr>
          </table>

          <div class="sp-32"></div>

          <!-- Help -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center">
              <p class="help" style="font-family:Arial,Helvetica,sans-serif; font-weight:800; font-size:22px; line-height:28px; color:#0F172A; margin:0;">Need Help?</p>
              <div class="sp-8"></div>
              <p class="help-call" style="font-family:Arial,Helvetica,sans-serif; font-size:16px; line-height:24px; color:#6B7280; margin:0;">
                Call: 
              </p>
            </td></tr>
          </table>

        </td></tr>
      </table>
      <!-- /Card -->
    </td></tr>
  </table>
</body>
</html>
