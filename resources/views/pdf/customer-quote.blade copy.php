<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Booking Confirmation</title>
<style>
  /* ====== Theme variables (tweak these to match colors precisely) ====== */
  :root{
    --page-bg: #f4f6f8;
    --card-bg: #ffffff;
    --accent: #083a57;     /* deep blue for headings/borders */
    --muted: #6b7280;
    --section-bg: #fbfcfd;
    --line: #dde6ee;
    --important: #b32b2b;  /* red for warnings/amount */
    --text: #111827;
    --mono: 'Helvetica Neue', Arial, sans-serif;
  }

  /* ====== Base ====== */
  body{
    margin:0;
    font-family: var(--mono);
    background: var(--page-bg);
    color:var(--text);
    -webkit-font-smoothing:antialiased;
    -moz-osx-font-smoothing:grayscale;
    padding:28px;
  }

  .wrap{
    max-width:900px;
    margin:18px auto;
    padding:18px;
  }

  .receipt {
    /* background: var(--card-bg);
    border: 2px solid var(--accent);
    border-radius:8px;
    overflow:hidden;
    box-shadow: 0 6px 18px rgba(6,20,30,0.08); */
  }

  /* ====== Header ====== */
  .topbar{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    padding:18px 22px;
    border-bottom:4px solid var(--accent);
    background:
      linear-gradient(180deg, rgba(8,58,87,0.02), transparent 60%);
  }
  .title {
    font-size:20px;
    font-weight:700;
    color:var(--accent);
    letter-spacing:0.4px;
    margin:0;
  }
  .booking-date {
    text-align:right;
    font-size:13px;
    color:var(--muted);
    margin-left:18px;
    min-width:180px;
  }
  .meta-inline {
    padding:14px 22px;
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    border-bottom:1px dashed var(--line);
    background:linear-gradient(180deg, transparent, #fff 40%);
  }
  .meta-inline .meta-left{ font-size:14px; color:var(--text); }
  .meta-inline .meta-right{ font-size:13px; color:var(--muted); text-align:right; }

  /* ====== Sections ====== */
  .sections {
    padding:18px 20px 24px 20px;
    display:grid;
    gap:14px;
  }

  .section {
    border-top: 2px solid #000 !important;
    border:px solid var(--line);
    /* border-radius:6px; */
    background:var(--section-bg);
    padding:12px 14px;
  }

  .section h3{
    margin:0 0 10px 0;
    font-size:13px;
    color:var(--accent);
    font-weight:700;
    letter-spacing:0.3px;
    border-bottom:1px solid #e6eef6;
    padding-bottom:8px;
  }

  /* label / value grid */
  .kv {
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:8px 16px;
    align-items:start;
    font-size:13px;
    color:var(--text);
  }
  .kv .k { color:#334155; font-weight:600; }
  .kv .v { color:#0f1724; font-weight:500; }

  /* single column rows stretch */
  .kv.full { grid-template-columns: 1fr; }

  /* small inline group for two values on same row */
  .row-inline {
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    font-size:13px;
  }

  /* Rate & important text */
  .rate {
    background:#fff;
    padding:8px;
    border-radius:6px;
    border:1px solid #e6eef6;
    display:inline-block;
    font-weight:700;
  }
  .rate .amount { color:var(--important); font-weight:800; }

  /* Pick-up / return address block (monospace-like spacing) */
  .address {
    font-size:13px;
    color:#0b1320;
    line-height:1.35;
    white-space:pre-line;
  }

  /* Notes / legal text */
  .notes {
    margin-top:8px;
    padding:12px;
    background:#fff;
    /* border-left:4px solid var(--accent);
    border-radius:4px; */
    font-size:12.25px;
    color:var(--muted);
    line-height:1.45;
  }

  .notes a { color:var(--accent); text-decoration:underline; }

  /* bottom small footer */
  .footer {
    padding:12px 22px;
    border-top:1px solid var(--line);
    font-size:12px;
    color:var(--muted);
    background:linear-gradient(0deg, rgba(0,0,0,0.01), transparent);
  }

  /* responsive */
  @media (max-width:640px){
    .kv { grid-template-columns:1fr; }
    .meta-inline { flex-direction:column; align-items:flex-start; gap:8px; }
    .booking-date { text-align:left; min-width:0; }
  }
</style>
</head>
<body>
  <div class="wrap">
    <article class="receipt" role="document" aria-label="Booking confirmation">
      <header class="topbar">
        <div>
          <h1 class="title">Booking Confirmation</h1>
          <div style="margin-top:6px; font-size:13px; color:var(--muted)">
            Thank you for booking with Vship. Please review the booking details below and advise us of any discrepancies and/or changes, if required
          </div>
        </div>

        <!-- Booking date (exact text from the PDF) -->
        <div class="booking-date">
          <div><strong>Booking Date :</strong> {{ $shipment->created_at->format('d/m/Y') }}</div>
          <div style="margin-top:8px; font-size:12px; color:var(--muted)">
            Booking Number : <strong>{{ $shipment->booking_number }}</strong><br>
            Reference Number : <strong>{{ $shipment->reference_number }}</strong>
          </div>
        </div>
      </header>
       <div>
          <h1 class="title">Booking Confirmation</h1>
          <div style="margin-top:6px; font-size:13px; color:var(--muted)">
            Thank you for booking with Vship. Please review the booking details below and advise us of any discrepancies and/or changes, if required
          </div>
        </div>

      <!-- slim meta line -->
      <div class="meta-inline">
        <div class="meta-left">Booking created by {{ $shipment->branch->user->fname }}</div>
        <div class="meta-right">PLEASE CONTACT {{ $shipment->branch->user->fname }} WITHIN 48 HOURS IF ANY CORRECTION TO THIS BOOKING IS NECESSARY</div>
      </div>

      <section class="sections">
        <!-- Customer Information -->
        <div class="section" id="customer-info">
          <h3>Customer Information</h3>
          <div class="kv">
            <div><span class="k">Shipper :</span> <span class="v">{{ $shipment->shipper_name ?? $shipment->branch->user->fname }}</span></div>
            <div><span class="k">Attn :</span> <span class="v">{{ $shipment->customer->user->fname }}</span></div>

            <div><span class="k">Tel No :</span> <span class="v">{{ $shipment->customer_phone ?? '-' }}</span></div>
            <div><span class="k">Fax :</span> <span class="v">{{ $shipment->customer->fax ?? '-' }}</span></div>

            <div class="full"><span class="k">Email :</span> <span class="v">{{ $shipment->customer->user->email ?? '-' }}</span></div>
          </div>
        </div>

        <!-- Shipping Information -->
        <div class="section" id="shipping-info">
          <h3>Shipping Information</h3>
          <div class="kv">
            <div><span class="k">Vessel :</span> <span class="v">{{ $shipment->vessel_aircraft_name ?? '-' }}</span></div>
            <div><span class="k">Voyage :</span> <span class="v">{{ $shipment->voyage_number ?? '-' }}</span></div>

            {{-- Pickups --}}
                  @if($shipment->pickups->count())
                      <h4>Pickups</h4>
                      <ul>
                          @foreach($shipment->pickups as $pickup)
                              <li>
                                <div><span class="k">Cargo Origin :</span> <span class="v">{{$pickup->address ?? '-'}}</span></div> <br>
                              </li>
                          @endforeach
                      </ul>
                  @endif

                  {{-- Dropoffs --}}
                  @if($shipment->dropoffs->count())
                      <h4>Dropoffs</h4>
                      <ul>
                          @foreach($shipment->dropoffs as $dropoff)
                              <li>
                                   <div><span class="k">Cargo Dropoff :</span> <span class="v">{{$dropoff->address ?? '-'}}</span></div> <br>
                              </li>
                          @endforeach
                      </ul>
                  @endif

            
            <div><span class="k">Origin Ramp Rail :</span> <span class="v">—</span></div>

            <div><span class="k">Port of Loading :</span> <span class="v">{{$shipment->place_of_delivery ?? '-'}}</span></div>
            <div><span class="k">Port of Discharge :</span> <span class="v">{{$shipment->port_of_discharge ?? '-'}}</span></div>

            <div><span class="k">Type of Booking :</span> <span class="v">{{$shipment->shipment_type ?? '-'}}</span></div>
            <div><span class="k">Equipment Type :</span> <span class="v">-</span></div>

            <div><span class="k">No.of Container :</span> <span class="v">1</span></div>
            <div><span class="k">Carrier :</span> <span class="v">{{$shipment->carrier->name ?? '-'}}</span></div>
          </div>
        </div>

        <!-- Estimated Cut-off Information -->
        <div class="section" id="cutoff-info">
          <h3>Estimated Cut-off Information</h3>
          <div class="kv">
            <div><span class="k">Port Cut off :</span> <span class="v">{{$quote->port_cutoff ?? '-'}}</span></div>
            <div><span class="k">Ramp Cut off :</span> <span class="v">06/09/2025</span></div>

            <div><span class="k">Earliest Receivable Date :</span> <span class="v">06/02/2025</span></div>

            <div><span class="k">Docs Cut off :</span> <span class="v">06/16/2025</span></div>
            <div><span class="k">Orig. Titles Cut off :</span> <span class="v">{{$quote->original_title_cut_off ?? '-'}}</span></div>

            <div><span class="k">ETD / Sailing Date :</span> <span class="v">{{$quote->esailing_dateta ?? '-'}}</span></div>
            <div><span class="k">ETA :</span> <span class="v">{{$quote->eta ?? '-'}}</span></div>
          </div>
        </div>

        <!-- Cargo Information -->
        <div class="section" id="cargo-info">
          <h3>Cargo Information</h3>
          <div class="kv">
            <div class="full"><span class="k">Commodity :</span> <span class="v">{{$shipment->commodity ?? '-'}}</span></div>
            <div><span class="k">Equipment Type :</span> <span class="v">-</span></div>
            <div><span class="k">Equipment Qty :</span> <span class="v">-</span></div>
          </div>
        </div>

        <!-- Pick-up/Return Information -->
        <div class="section" id="pickup">
          <h3>Pick-up / Return Information</h3>
          <div class="kv">
            <div class="full address">

               {{-- Pickups --}}
                  @if($shipment->pickups->count())
                      <h4>Pickups</h4>
                      <ul>
                          @foreach($shipment->pickups as $pickup)
                              <li>
                                
                                <strong>Empty Pick-up :</strong> {{$pickup->city ?? '-' }}
                                <br><strong>Address :</strong> {{$pickup->address ?? '-'  }}
                                <br><strong>Tel :</strong> {{$shipment->branch->phone ?? '-' }}
                                {{-- <div><span class="k">Cargo Origin :</span> <span class="v">{{$pickup->address ?? '-'}}</span></div> <br> --}}
                              </li>
                          @endforeach
                      </ul>
                  @endif
            </div>
            
            <div class="full address">
                  {{-- Dropoffs --}}
                  @if($shipment->dropoffs->count())
                      <h4>Dropoffs</h4>
                      <ul>
                          @foreach($shipment->dropoffs as $dropoff)
                              <li>
                              <strong>Load Return :</strong> {{$dropoff->city ?? '-' }}
                              <br><strong>Address :</strong> {{$dropoff->address ?? '-'  }}
                              <br><strong>Tel :</strong> {{$shipment->branch->phone ?? '-' }}
                                   {{-- <div><span class="k">Cargo Dropoff :</span> <span class="v">{{$dropoff->address ?? '-'}}</span></div> <br> --}}
                              </li>
                          @endforeach
                      </ul>
                      
                  @endif
            </div>

            <div class="full address">
              <strong>Address:</strong> {{$shipment->place_of_delivery ?? '-'}}
              <br><strong>Earliest Receivable Date :</strong> {{$quote->earliest_recievable_date ?? '-'}}
            </div>
          </div>
        </div>

        <!-- Rate and Staff -->
        <div class="section" id="rate-staff">
          <h3>Rate Agreed & Booking Staff</h3>
          <div class="row-inline" style="margin-bottom:8px;">
            <div class="rate">RATE AGREED: <span class="amount">$ {{$quote->quoted_amount ?? '-'}}</span> <small style="color:var(--muted); font-weight:600"></small></div>
            <div style="text-align:right;">
              <div><strong>Booking Staff :</strong> {{$shipment->branch->user->fname ?? '-'}}</div>
              <div><strong>Email Address :</strong> {{$shipment->branch->user->email ?? '-'}}</div>
            </div>
          </div>

          <div class="notes">
            MUST FOLLOW THE AUTO CUT OFF IF NOT THEN CONTAINER WILL BE ROLLED WITH CHARGES.
            <br><br>
            No cars over 10 years is allowed to Liberia. It has to be 2015 year vehicles or newer. BIC No. is required on all documentation prior to cargo loading. For BIC please visit:
            <br><a href="http://www.bureauveritas.com/wps/wcm/connect/bv_com/group/home/about-us/our-business/international-trade/gsit-list-of-contracts" target="_blank" rel="noopener">bureauveritas — GSIT list of contracts</a>
            <br><br>
            By using this booking you hereby acknowledge the acceptance of the listed rate, which is subject to any additional charges billed due to Govt action or incidentals billed for account of cargo not included in the original rate agreement.
          </div>
        </div>

        <!-- Booking remarks -->
        <div class="section" id="remarks">
          <h3>Booking remarks</h3>
          <div class="kv full">
            <div><strong>Date time :</strong> {{ $quote->created_at ? $quote->created_at->format('h:i A') : '-' }}</div>
          </div>
        </div>
      </section>

      <footer class="footer">
        PLEASE CONTACT Vship WITHIN 48 HOURS IF ANY CORRECTION TO THIS BOOKING IS NECESSARY.
      </footer>
    </article>
  </div>
</body>
</html>
