<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Uqoye is Ready!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .notification-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }

        .icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }

        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
            font-size: 16px;
        }

        .cta-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .cta-button:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .order-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .order-details h3 {
            color: #333;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="notification-card">
        <div class="icon">✓</div>
        <h1>Your Quote is Ready!</h1>
        <p>Hello {{$shipment->customer->user->fname ?? ''}} Kindly find the attached booking comfirmation details for your reference. Note we await your response to accept or reject this booking to proceed with creating your shipment</p>

        <p>Please visit us during our business hours to collect your order.</p>
        
        <a href="{{env('FRONTEND_URL') . '/view_loads_single.html?id=' . base64_encode($this->shipment->id)}}" class="cta-button">View shipment</a>
        
        <p style="margin-top: 20px; font-size: 14px; color: #888;">
            Need help? Contact us at {{optional($branch->user)->email ?? '' }} {{ optional($branch->user)->email ?? '' }}
        </p>
    </div>
</body>
</html>