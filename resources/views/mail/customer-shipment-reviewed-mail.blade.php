<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shipment Uploaded</title>
  <style>
    /* General Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: Arial, sans-serif;
  background: #f8f9fa;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
}

/* Card Container */
.card {
  background: #fff;
  border-radius: 12px;
  padding: 40px 30px;
  text-align: center;
  width: 500px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Logo */
.logo img {
  width: 150px;
  margin-bottom: 20px;
}

/* Heading */
h2 {
  font-size: 20px;
  font-weight: 700;
  font-family: sans-serif;
  margin-bottom: 15px;
  padding: 10px;
}

/* Message */
.message {
  font-size: 15px;
  color: #333;
  margin-bottom: 20px;
}

/* Tracking Box */
.tracking-box {
  background: #153C96;
  color: #fff;
  font-weight: bold;
  padding: 12px;
  border-radius: 6px;
  font-size: 18px;
  margin-bottom: 20px;
}

/* Tracking Number */
.tracking-number {
  font-size: 14px;
  margin-bottom: 15px;
}

/* Barcode */
.barcode img {
  margin: 10px auto;
  display: block;
  height: 70px;
}
.barcode p {
  font-size: 13px;
  margin-top: 5px;
}

/* Footer */
.footer {
  font-size: 14px;
  margin-top: 20px;
  margin-bottom: 25px;
  color: #333;
}

/* Help Section */
.help {
  font-size: 15px;
}
.help strong {
  font-weight: bold;
}

  </style>
</head>
<body>
  <div class="card">
    <!-- Logo -->
    <div class="logo">
      <img src="https://s3.eu-central-2.wasabisys.com/smileslogistics/assets/images/logo-dark.png" alt="Smiles Logistics Group">
    </div>

    <!-- Heading -->
    <h2>Shipment Uploaded</h2>

    <!-- Message -->
    <p class="message">
      Hello, your shipment has been reviewed and created successfully  </p>

    <!-- Tracking Box -->
  <div class="tracking-box">
    {{ $shipment->shipment_tracking_number }}
  </div>

  <!-- Tracking Number -->
  <p class="tracking-number">
    Tracking Number: <span>{{ $shipment->shipment_tracking_number }}</span>
  </p>

  <!-- Barcode -->
  <div class="barcode">
    <img src="https://barcode.tec-it.com/barcode.ashx?data={{ $shipment->shipment_tracking_number }}&code=Code128&translate-esc=true" alt="barcode">
    <p>{{ $shipment->shipment_tracking_number }}</p>
  </div>

  <!-- View Shipment Button -->
<p style="margin: 25px 0;">
  <a href="{{ env('FRONTEND_URL') }}/view_settlement_single.html?id={{ base64_encode($shipment->id) }}"
     style="display:inline-block; background-color:#153C96; color:#fff; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:14px;">
    View Shipment
  </a>
</p>

  <!-- Footer -->
  <p class="footer">
    Warm Regards,<br>
    <strong>{{ optional($branch->user)->fname ?? '' }} {{ optional($branch->user)->lname ?? '' }}</strong>
  </p>

    <!-- Help -->
    <p class="help">
      <strong>Need Help?</strong> &nbsp; Call: +100 000 000
    </p>
  </div>
</body>
</html>
