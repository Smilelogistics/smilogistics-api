<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vship Booking Confirmation</title>
    <style>
        /* Importing Font (Plain CSS method) */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap');
        
        /* Base Styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f7f7;
            padding: 20px;
            line-height: 1.5;
            color: #111827;
        }

        .document-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            border-radius: 8px;
        }

        /* Header and Logo */
        /* Using display:flex for modern email clients, but keeping structure simple for compatibility */
        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-placeholder {
            width: 60px;
            height: 60px;
            background-color: #1e3a8a; /* Dark Blue */
            border-radius: 50%;
        }

        .logo-title {
            font-size: 2.25rem;
            font-weight: 900;
            color: #1e3a8a; 
            line-height: 1;
        }

        .address-group {
            font-size: 0.75rem;
            text-align: right;
            color: #4b5563;
        }
        
        .address-group p {
            margin: 0;
        }

        .address-group a {
            color: #2563eb;
            text-decoration: none;
        }

        /* Main Confirmation Title */
        .booking-header {
            color: #0c4a6e;
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        /* Section Titles */
        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0c4a6e;
            margin-top: 24px;
            margin-bottom: 12px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }

        /* Introductory Text */
        .intro-text {
            font-size: 0.875rem;
            color: #4b5563;
            margin-bottom: 24px;
        }

        /* Booking Summary Bar (Using table for robust horizontal layout) */
        .booking-summary-bar {
            width: 100%;
            margin-bottom: 24px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            border-collapse: collapse;
        }

        .booking-summary-bar td {
            padding: 8px 12px;
        }

        .booking-summary-bar .booking-number {
            font-size: 1.125rem;
            font-weight: 700;
            color: #1f2937;
        }

        .booking-summary-bar .booking-number span {
            color: #0c4a6e;
            margin-left: 8px;
        }

        .booking-summary-bar .ref-number {
            font-weight: 600;
            color: #4b5563;
            text-align: right;
        }


        /* Data Tables (Core Layout) */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            margin-bottom: 24px;
        }

        .data-table td {
            padding-top: 4px;
            padding-bottom: 4px;
            vertical-align: top;
        }

        .data-table .label {
            font-weight: 500;
            color: #374151;
            padding-right: 15px; 
        }

        .data-table .value {
            font-weight: 400;
            color: #111827; 
            word-break: break-word; 
        }
        
        .data-table .value.bold {
            font-weight: 700;
        }
        
        .data-table .value.accent-yellow {
            color: #f59e0b;
        }
        
        .data-table .value.accent-green {
            color: #047857;
        }
        
        .data-table .value a {
            color: #2563eb;
            text-decoration: none;
        }
        
        /* Styles for 2 data pairs per row (4 columns total) */
        .data-table .w-30 {
            width: 30%; /* Label width for 4-column layout */
            padding-right: 8px;
        }
        .data-table .w-20 {
            width: 20%; /* Value width for 4-column layout */
        }


        /* Two-Column Layout for Cargo/Pick-up Info */
        .two-col-wrapper {
            overflow: hidden; /* clearfix */
            margin-bottom: 8px;
        }

        .two-col-wrapper > div {
            width: 100%;
            box-sizing: border-box; 
            float: none;
            margin-bottom: 24px;
        }

        /* Responsive behavior using floats for two columns on wider screens */
        @media (min-width: 768px) {
            .two-col-wrapper > div {
                float: left;
                width: 50%;
                padding-right: 16px;
            }
            .two-col-wrapper > div:last-child {
                padding-right: 0;
                padding-left: 16px;
            }
        }

        /* Rate and Staff Section */
        .rate-staff-section {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #d1d5db;
        }

        .staff-info {
            text-align: right;
            padding-left: 15px;
        }

        .staff-info p {
            margin: 0;
        }

        .staff-info .staff-name {
            font-weight: 600;
            color: #4b5563;
        }

        .staff-info .staff-email {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .staff-info .staff-email span {
            color: #2563eb;
        }

        /* Warnings and Remarks */
        .remarks-section {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #d1d5db;
        }
        
        .warning-box {
            color: #b91c1c;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 8px;
            padding: 8px;
            background-color: #fef2f2;
            border-radius: 4px;
        }

        .bic-info {
            font-size: 0.75rem;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .bic-info a {
            color: #2563eb;
            word-break: break-all;
        }

        .acknowledgement {
            font-size: 0.75rem;
            color: #4b5563;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            font-style: italic;
        }

        .date-time {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 16px;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="document-container">
        
        <!-- HEADER SECTION (Logo and Company Info) -->
        <header class="document-header">
            <div class="logo-group">
                 <!-- Placeholder for the Vship Logo image -->
                 <img src="{{$shipment->branch->logo}}" alt="{{$shipment->branch->user->fname}}" class="logo-placeholder" style="border-radius: 50%;">
                <div>
                    <h1 class="logo-title">{{$shipment->branch->user->fname}}</h1>
                </div>
            </div>
            
            <div class="address-group">
                <address style="font-style: normal;">
                    {{$shipment->branch->address}}<br>
                    {{$shipment->branch->city}}, {{$shipment->branch->state}}. {{$shipment->branch->zip}}
                </address>
                <p>Tel: {{$shipment->branch->phone}}, Fax: {{$shipment->branch->fax}}</p>
                <p>web: <a href="http://{{$shipment->branch->website}}">{{$shipment->branch->website}}</a></p>
            </div>
        </header>

        <!-- BOOKING CONFIRMATION TITLE -->
        <h2 class="booking-header">Booking Confirmation</h2>
        
        <p class="intro-text">
            Booking Date: <span style="font-weight: 600;">{{ $shipment->created_at->format('d/m/Y') }}</span>
        </p>

        <p class="intro-text" style="color: #6b7280;">
            Thank you for booking with Vship. Please review the booking details below and advise us of any 
            discrepancies and/or changes, if required.
        </p>

        <!-- BOOKING & REFERENCE NUMBERS -->
        <!-- Converted to table for robust horizontal layout -->
        <table class="booking-summary-bar">
            <tr>
                <td style="width: 50%;">
                    <p class="booking-number">Booking Number: <span>{{ $shipment->booking_number ?? '-' }}</span></p>
                </td>
                <td style="width: 50%;">
                    <p class="ref-number">Reference Number: <span>{{ $shipment->reference_number ?? '-' }}</span></p>
                </td>
            </tr>
        </table>

        <!-- CUSTOMER INFORMATION -->
        <h3 class="section-title">Customer Information</h3>
        <!-- Converted data-grid to data-table -->
       
          <table class="data-table">
            <tbody>
                <tr>
                    <!-- Pair 1 -->
                    <td class="label w-30">Shipper</td>
                    <td class="value w-20">{{ $shipment->shipper_name ?? '-' }}</td>
                    <!-- Pair 2 -->
                    <td class="label w-30">Attn</td>
                    <td class="value w-20">{{ $shipment->customer->user->fname ?? '-' }}</td>
                </tr>
                <tr>
                    <!-- Pair 3 -->
                    <td class="label w-30">Tel No</td>
                    <td class="value w-20">{{ $shipment->customer->customer_phone ?? '-' }}</td>
                    <!-- Pair 4 -->
                    <td class="label w-30">Fax</td>
                    <td class="value w-20">—</td>
                </tr>
                <tr>
                    <!-- Pair 5 (Single entry in this row) -->
                    <td class="label w-30">Email</td>
                    <td class="value w-20"><a href="mailto:{{ $shipment->customer->user->email ?? '-' }}">{{ $shipment->customer->user->email ?? '-' }}</a></td>
                    <!-- Empty Placeholder cells -->
                    <td class="label w-30"></td>
                    <td class="value w-20"></td>
                </tr>
            </tbody>
        </table>

        <!-- SHIPPING INFORMATION -->
        <h3 class="section-title">Shipping Information</h3>
        <!-- Converted data-grid to data-table -->
       
        <table class="data-table">
            <tbody>
                <tr>
                    <!-- Pair 1 -->
                    <td class="label w-30">Vessel</td>
                    <td class="value w-20">{{ $shipment->vessel_aircraft_name ?? '-' }}</td>
                    <!-- Pair 2 -->
                    <td class="label w-30">Voyage</td>
                    <td class="value w-20">{{ $shipment->voyage_number ?? '-' }}</td>
                </tr>
                <tr>
                    <!-- Pair 3 -->
                    <td class="label w-30">Cargo Origin</td>
                    <td class="value w-20">{{ $shipment->origin_port ?? '-' }}</td>
                    <!-- Pair 4 -->
                    <td class="label w-30">Origin Ramp Rail</td>
                    <td class="value w-20">—</td>
                </tr>
                <tr>
                    <!-- Pair 5 (Accent) -->
                    <td class="label w-30">Port of Loading:</td>
                    <td class="value w-20 bold accent-yellow">{{ $shipment->port_of_delivery ?? '-' }}</td>
                    <!-- Pair 6 (Accent) -->
                    <td class="label w-30">Port of Discharge:</td>
                    <td class="value w-20 bold accent-yellow">{{ $shipment->port_of_discharge ?? '-' }}</td>
                </tr>
                <tr>
                    <!-- Pair 7 -->
                    <td class="label w-30">Type of Booking:</td>
                    <td class="value w-20"> - </td>
                    <!-- Pair 8 -->
                    <td class="label w-30">Equipment Type:</td>
                    <td class="value w-20">{{ $shipment->equipment_type ?? '-' }}</td>
                </tr>
                <tr>
                    <!-- Pair 9 -->
                    <td class="label w-30">No. of Container:</td>
                    <td class="value w-20">{{ $shipment->ocean_total_containers_in_words ?? '-' }}</td>
                    <!-- Pair 10 -->
                    <td class="label w-30">Carrier:</td>
                    <td class="value w-20">{{ $shipment->carrier->name ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
        
        <!-- ESTIMATED CUT-OFF INFORMATION -->
        <h3 class="section-title">Estimated Cut-off Information</h3>
        <!-- Converted data-grid to data-table -->
     
        <table class="data-table">
            <tbody>
                <tr>
                    <!-- Pair 1 -->
                    <td class="label w-30">Port Cut off:</td>
                    <td class="value w-20">{{ $quote->port_cut_off ?? '-' }}</td>
                    <!-- Pair 2 -->
                    <td class="label w-30">Ramp Cut off:</td>
                    <td class="value w-20">{{ $quote->ramp_cut_off ?? '-' }}</td>
                </tr>
                <tr>
                    <!-- Pair 3 -->
                    <td class="label w-30">Earliest Receivable Date:</td>
                    <td class="value w-20">{{ $quote->earliest_recievable_date ?? '-' }}</td>
                    <!-- Pair 4 -->
                    <td class="label w-30">Docs Cut off:</td>
                    <td class="value w-20">{{ $quote->docs_cut_off ?? '-' }}</td>
                </tr>
                <tr>
                    <!-- Pair 5 -->
                    <td class="label w-30">Orig. Titles Cut off:</td>
                    <td class="value w-20">{{ $quote->original_title_cut_off ?? '-' }}</td>
                    <!-- Pair 6 (Accent Colors) -->
                    <td class="label w-30">ETD/Sailing Date:</td>
                    <td class="value w-20 bold accent-green">{{ $quote->esailing_dateta ?? '-' }}</td>
                </tr>
                
                <tr>
                    <!-- Pair 7 -->
                    <td class="label w-30">ETA:</td>
                    <td class="value w-20 bold accent-green">{{ $quote->eta ?? '-' }}</td>
                    <!-- Empty Placeholder cells to maintain 4-column structure -->
                    <td class="label w-30"></td>
                    <td class="value w-20"></td>
                </tr>
            </tbody>
        </table>

        <!-- CARGO & PICK-UP/RETURN INFORMATION -->
        <!-- Two-column layout using responsive floats/widths instead of flex/grid -->
        <div class="two-col-wrapper">
            <div>
                <h3 class="section-title" style="margin-top: 0;">Cargo Information</h3>
                <!-- Converted data-grid to data-table -->
                <table class="data-table" style="margin-bottom: 0;">
                    <tbody>
                        <tr>
                            <td class="label">Commodity:</td>
                            <td class="value">{{ $shipment->commodity ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Equipment Type:</td>
                            <td class="value">{{ $shipment->equipment_type ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Equipment Qty:</td>
                            <td class="value">{{ $shipment->equipment_quantity ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div>
                <h3 class="section-title" style="margin-top: 0;">Pick-up/Return Information</h3>
                <!-- Converted data-grid to data-table -->
                <table class="data-table" style="margin-bottom: 0;">
                    <tbody>
                       @if($shipment->pickups->count())
                          @foreach($shipment->pickups as $pickup)
                            <tr>
                              <td class="label">{{$pickup->pickup_type ?? '-'}}</td>
                              <td class="value">{{$pickup->location_name ?? '-'}}</td>
                          </tr>
                          <tr>
                              <td class="label">Address</td>
                              <td class="value">{{$pickup->address ?? '-'}}</td>
                          </tr>
                          <tr>
                              <td class="label">Tel</td>
                              <td class="value">{{$pickup->pickup_contact_no ?? '-'}}</td>
                          </tr>
                          @endforeach
                  @endif

                      {{-- Dropoffs --}}
                  @if($shipment->dropoffs->count())
                          @foreach($shipment->dropoffs as $dropoff)
                              <tr>
                                <td class="label">{{$dropoff->dropoff_type ?? '-'}}</td>
                                <td class="value">{{$dropoff->location_name ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="label">Address:</td>
                                <td class="value">{{$dropoff->address ?? '-'}}</td>
                            </tr>
                            <tr>
                                <td class="label">Earliest Receivable Date</td>
                                <td class="value">{{$dropoff->earliest_recievable_date ?? '-'}}</td>
                            </tr>
                          @endforeach
                  @endif
                        
                        
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- RATE & STAFF -->
        <div class="rate-staff-section">
            <!-- Converted to table for robust horizontal layout -->
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <!-- Rate Agreed Column -->
                    <td style="width: 50%; padding-right: 15px; vertical-align: top;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr>
                                <td class="label" style="width: auto; padding-right: 10px; font-size: 1rem; font-weight: 700; color: #1f2937;">RATE AGREED:</td>
                                <td class="value" style="width: auto; font-size: 1rem; font-weight: 700; color: #dc2626;">{{$shipment->branch->currency . ' ' . $quote->quoted_amount ?? '-'}}</td>
                            </tr>
                        </table>
                    </td>
                    <!-- Staff Info Column -->
                    <td class="staff-info" style="width: 50%; text-align: right; vertical-align: top;">
                        <p class="staff-name">Booking Staff: <span style="font-weight: 400; margin-left: 4px;">{{$quote->user->fname ?? '-'}}</span></p>
                        <p class="staff-email">Email Address: <span style="font-weight: 400; margin-left: 4px;"><a href="mailto:{{$quote->user->email ?? '-'}}">{{$quote->user->email ?? '-'}}</a></span></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- WARNINGS & REMARKS -->
        <div class="remarks-section">
            <p class="warning-box">
                PLEASE CONTACT Vship WITHIN 48 HOURS IF ANY CORRECTION TO THIS BOOKING IS NECESSARY<br>
                MUST FOLLOW THE AUTO CUT OFF IF NOT THEN CONTAINER WILL BE ROLLED WITH CHARGES
            </p>
            <p class="bic-info">
                No cars over 10 years is allowed to Liberia. It has to be 2015 year vehicles or newer. BIC No. is required on all 
                documentation prior to cargo loading. For BIC please visit 
                <a href="http://www.bureauveritas.com/wps/wcm/connect/bv_com/group/home/about-us/our-business/international-trade/gsit-list-of-contracts">http://www.bureauveritas.com/wps/wcm/connect/bv_com/group/home/about-us/our-business/international-trade/gsit-list-of-contracts</a>
            </p>
            <p class="acknowledgement">
                By using this booking You hereby acknowledge the acceptance of the listed rate, which is subject to any additional charges billed due to 
                Govt action or incidentals billed for account of cargo not included in the original rate agreement.
            </p>
            <p class="date-time">
                Date time: {{$quote->created_at->format('d/m/Y H:i:s A')}}
            </p>
        </div>

    </div>

</body>
</html>
