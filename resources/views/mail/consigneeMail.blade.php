<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smile Logistics</title>
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
                <i>Smile</i><span style="color:lightcoral">Logistics</span></h2>
            </td>
          </tr>
          @php
            $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
         @endphp
         

  
          <tr>
            <td style="text-align: center;">
              <h4 style="margin: 0px;padding-bottom: 25px; text-transform: uppercase; font-size:22px;">Shipment uploaded succesfullt</h4>
              <!-- <h2 style="margin: 0px;padding-bottom: 25px;font-size:22px;"> Please renew your subscription</h2> -->
              <p style=" margin: 0px 40px;padding-bottom: 25px;line-height: 2; font-size: 15px;">Hello {{$shipment['consignee']}} your your shipment has been uploaded successfully with tracking number <b> {{$shipment['shipment_tracking_number']}} </b> <br>
              Tracking Number: {{$shipment['shipment_tracking_number']}} <br> 
              {!! $generator->getBarcode(strval($shipment['shipment_tracking_number']), $generator::TYPE_CODE_128) !!}
              </p>
              <p style=" margin: 0px 32px;padding-bottom: 25px;line-height: 2; font-size: 15px;"> Warm Regards, <br>
            {{ config('app.name') }}
              </p>
            </td>
          </tr>
        
          <tr>
            <td style="text-align:center;">
              <h2 style="padding-top: 25px; line-height: 1; margin:0px;">Need Help?</h2>
              <div style="margin-bottom: 25px; font-size: 15px;margin-top:7px;">Give us a call Sample-1800
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