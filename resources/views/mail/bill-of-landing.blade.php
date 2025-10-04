<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Bill of Lading is Ready!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .shipment-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .shipment-details h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-label {
            font-weight: bold;
            color: #495057;
        }

        .detail-value {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="notification-card">
        <div class="icon">📦</div>
        <h1>Your Bill of Lading is Ready!</h1>
        
        <p>Hello {{ $shipment->customer->user->fname ?? 'Customer' }},</p>
        
        <p>Kindly find the attached Bill of Lading document for your shipment. This document contains all the necessary details for your ocean freight shipment.</p>

        <div class="shipment-details">
            <h3>Shipment Details</h3>
            <div class="detail-row">
                <span class="detail-label">Tracking Number:</span>
                <span class="detail-value">{{ $shipment->shipment_tracking_number ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Vessel:</span>
                <span class="detail-value">{{ $shipment->vessel_aircraft_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Voyage Number:</span>
                <span class="detail-value">{{ $shipment->voyage_number ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Port of Loading:</span>
                <span class="detail-value">{{ $shipment->port_of_loading ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Port of Discharge:</span>
                <span class="detail-value">{{ $shipment->port_of_discharge ?? 'N/A' }}</span>
            </div>
        </div>

        <p><strong>Please review the attached PDF document for complete shipment details.</strong></p>
        
        <a href="{{ env('FRONTEND_URL') . '/view_loads_single.html?id=' . base64_encode($shipment->id) }}" class="cta-button">View Shipment Details</a>
        
        <p style="margin-top: 20px; font-size: 14px; color: #888;">
            Need help? Contact us at {{ $branch->user->email ?? 'support@smileslogistics.com' }} or call {{ $branch->phone ?? 'N/A' }}
        </p>
    </div>
</body>
</html>