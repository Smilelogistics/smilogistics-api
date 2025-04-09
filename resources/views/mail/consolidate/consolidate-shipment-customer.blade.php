<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ optional($branch->user)->fname ?? '' }} {{ optional($branch->user)->lname ?? '' }}</title>
</head>
<body bgcolor="#0f3462" style="margin-top:20px;margin-bottom:20px">
  <!-- Main table -->
  <table border="0" align="center" cellspacing="0" cellpadding="0" bgcolor="white" width="650">
    <tr>
      <td>
        <!-- Child table -->
        <table border="0" cellspacing="0" cellpadding="0" style="color:#0f3462; font-family: sans-serif;">
          <tr>
            <td>
              <h2 style="text-align:center; margin: 0px; padding-bottom: 25px; margin-top: 25px;">
                <i>{{ optional($branch->user)->fname ?? '' }} {{ optional($branch->user)->lname ?? '' }} Mile</i><span style="color:lightcoral">Logistics</span></h2>
            </td>
          </tr>
          @php
            $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
          @endphp
  
          <tr>
            <td style="text-align: center;">
              <h4 style="margin: 0px;padding-bottom: 25px; text-transform: uppercase; font-size:22px;">Consolidate Shipment uploaded successfully</h4>
              <p style=" margin: 0px 40px;padding-bottom: 25px;line-height: 2; font-size: 15px;">
                Hello, your Shipment has been uploaded successfully with tracking number <b>{{ $consolidateShipment['consolidate_tracking_number'] }}</b>
                <br>
                Tracking Number: {{ $consolidateShipment['consolidate_tracking_number'] }} <br> 
                <span style="text-align:center; margin: auto;">
                  {!! $generator->getBarcode(strval($consolidateShipment['consolidate_tracking_number']), $generator::TYPE_CODE_128) !!}
                </span>
              </p>
              <p style=" margin: 0px 32px;padding-bottom: 25px;line-height: 2; font-size: 15px;">
                Warm Regards, <br>
                {{ optional($branch->user)->fname ?? '' }} {{ optional($branch->user)->lname ?? '' }}
              </p>
            </td>
          </tr>
          <tr>
            <td style="text-align:center;">
              <h2 style="padding-top: 25px; line-height: 1; margin:0px;">Need Help?</h2>
              <div style="margin-bottom: 25px; font-size: 15px;margin-top:7px;">
                Give us a call {{ optional($branch)->phone ?? '' }}
              </div>
            </td>
          </tr>
        </table>
        <!-- /Child table -->
      </td>
    </tr>
  </table>
  <!-- / Main table -->
</body>
</html>