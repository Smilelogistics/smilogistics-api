<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapag-Lloyd Bill of Lading Copy</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for a document-like appearance */
        :root {
            --bl-border: 1px solid #000;
            --bl-bg: #fff;
            --bl-text-color: #000;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f0f0;
            color: var(--bl-text-color);
            -webkit-print-color-adjust: exact; /* For better print rendering */
            print-color-adjust: exact;
        }

        .bill-of-lading {
            max-width: 8.5in; /* Standard US Letter width */
            margin: 2rem auto;
            background-color: var(--bl-bg);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .bl-box {
            border: var(--bl-border);
            padding: 0.5rem;
            min-height: 4rem;
        }

        .bl-box-header {
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            color: #4a4a4a;
            padding-bottom: 0.2rem;
        }

        .bl-content-text {
            font-size: 0.85rem;
            font-weight: 700;
            white-space: pre-wrap; /* Preserve line breaks in content */
        }
        
        /* Grid setup for the main description table */
        .description-grid {
            display: grid;
            grid-template-columns: 2fr 3fr 1fr 1fr; /* Container | Description | Weight | Measure */
            border-left: var(--bl-border);
            border-right: var(--bl-border);
            border-bottom: var(--bl-border);
        }

        .description-grid-header > div,
        .description-grid-row > div {
            border-right: var(--bl-border);
            border-bottom: var(--bl-border);
            padding: 0.25rem 0.5rem;
            min-height: 3rem;
            overflow-wrap: break-word;
        }

        .description-grid-header > div:last-child,
        .description-grid-row > div:last-child {
            border-right: none;
        }

        /* Remove border-bottom from the last row of the main table */
        .description-grid-row:last-of-type > div {
            border-bottom: none;
        }

        /* Print-specific adjustments */
        @media print {
            .bill-of-lading {
                margin: 0;
                box-shadow: none;
            }
            .bl-box, .description-grid {
                page-break-inside: avoid;
            }
        }

    </style>
</head>
<body>

<div class="bill-of-lading border border-gray-900 rounded-md p-2 bg-white text-xs">

    <!-- TOP HEADER ROW -->
    <div class="flex justify-between items-start mb-2 border-b border-black pb-1">
        <div class="font-bold text-sm">
            Carrier: <span class="font-normal">{{$shipment->carrier->name ?? '-'}}</span>
        </div>
        <div class="text-right">
            <div class="text-xl font-extrabold text-blue-800">Hapag-Lloyd</div>
            <div class="text-base font-bold">Bill of Lading</div>
        </div>
    </div>
    
    <!-- ADDRESS/REFERENCE GRID (4x2 layout) -->
    <div class="grid grid-cols-4 border-t border-r border-b border-l border-black -mt-1">
        
        <!-- Shipper Block -->
        <div class="col-span-2 bl-box !border-r-0 !border-b-0">
            <div class="bl-box-header">Shipper:</div>
            <pre class="bl-content-text !font-normal">
                {{ strtoupper($shipment->shipper_name ?? $branch->user->fname . ' ' . $branch->user->lname) }}
                {{ strtoupper($branch->address ?? '') }}

                {{ strtoupper($branch->company_name ?? 'SMILES LOGISTICS GROUP') }}
                {{ strtoupper($branch->company_address ?? '2646 HIGHWAY AVE') }}
                {{ strtoupper($branch->company_city ?? 'HIGHLAND, INDIANA 46322') }}
                PHONE: {{ strtoupper($branch->phone ?? 'N/A') }}
                EMAIL: {{ strtoupper($branch->user->email ?? 'N/A') }}
                </pre>
        </div>
        
        <!-- Carrier/Export Ref Block -->
        <div class="col-span-2 grid grid-rows-2">
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Carrier's Reference: B/L-No.:</div>
                <div class="bl-content-text">32756986 &nbsp; HLCUBSC2507BXQX8</div>
            </div>
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Export References:</div>
               <div class="bl-content-text">{{ strtoupper($shipment->ocean_shipper_reference_number ?? '-') }}</div>
            </div>
        </div>

        <!-- Consignee Block -->
        <div class="col-span-2 bl-box !border-r-0 !border-b-0">
            <div class="bl-box-header">Consignee (not negotiable unless consigned to order):</div>
            <pre class="bl-content-text !font-normal">{{strtoupper($shipment->consignee) ?? '-' }}
{{-- NO 9 PA KEVIN STREET
KETU IJANIKI LAGOS, NIGERIA --}}
PHONE: {{strtoupper($shipment->consignee_phone) ?? '-'}}
EMAIL: {{strtoupper($shipment->consignee_email) ?? '-'}}</pre>
        </div>

        <!-- Forwarding Agent Block -->
        <div class="col-span-2 bl-box !border-r-0 !border-b-0">
            <div class="bl-box-header">Forwarding Agent: F.M.C.NO:</div>
            <pre class="bl-content-text !font-normal"> {{strtoupper($shipment->branch->user->fname) ?? '-'}} </pre> 
        </div>

        <!-- Notify Party Block -->
        <div class="col-span-2 bl-box !border-r-0 !border-b-0">
            <div class="bl-box-header">Notify Address (Carrier not responsible for failure to notify...):</div>
            <pre class="bl-content-text !font-normal"> {{strtoupper($shipment->notify_party_name) ?? '-'}}
{{-- NO 9 PA KEVIN STREET
KETU IJANIKI LAGOS, NIGERIA --}}
PHONE: {{strtoupper($shipment->notify_party_phone) ?? '-'}}
EMAIL: {{strtoupper($shipment->notify_party_email) ?? '-'}}</pre>
        </div>

        <!-- Consignee's Reference / Place of Receipt -->
        <div class="col-span-2 grid grid-rows-2">
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Consignee's Reference:</div>
                <div class="bl-content-text"></div>
            </div>
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Place of Receipt:</div>
                <div class="bl-content-text">MINNEAPOLIS, MN</div>
            </div>
        </div>

        <!-- Vessel/Voyage/Ports Grid (3x2) -->
        <div class="col-span-4 grid grid-cols-6 border-b border-black">
            <div class="col-span-2 bl-box !border-b-0 !border-r-0">
                <div class="bl-box-header">Vessel(s):</div>
                <div class="bl-content-text">{{ strtoupper($shipment->vessel_aircraft_name ?? '-') }}</div>

            </div>
            <div class="col-span-2 bl-box !border-b-0 !border-r-0">
                <div class="bl-box-header">Port of Loading:</div>
                <div class="bl-content-text"> {{strtoupper($shipment->port_of_loading) ?? '-'}} </div>
            </div>
            <div class="col-span-2 bl-box !border-b-0">
                <div class="bl-box-header">Voyage-No. / Place of Delivery:</div>
                <div class="bl-content-text">{{ strtoupper($shipment->voyage_number ?? 'N/A') }} / {{ strtoupper($shipment->place_of_delivery ?? '-') }}</div>
            </div>
            <div class="col-span-3 bl-box !border-r-0">
                <div class="bl-box-header">Port of Discharge:</div>
                <div class="bl-content-text"> {{strtoupper($shipment->port_of_discharge) ?? '-'}} </div>
            </div>
            <div class="col-span-3 bl-box">
                <div class="bl-box-header">Multimodal Transport or Port to Port Shipment</div>
                <div class="bl-content-text font-normal text-xs text-right pt-2">COPY</div>
            </div>
        </div>
    </div>

    <!-- GOODS DESCRIPTION TABLE -->
    <div class="description-grid-header grid description-grid border-t-0">
        <div class="bl-box-header text-center !p-1">Container Nos., Seal Nos.; Marks and Nos.</div>
        <div class="bl-box-header text-center !p-1">Number and Kind of Packages, Description of Goods</div>
        <div class="bl-box-header text-center !p-1">Gross Weight:</div>
        <div class="bl-box-header text-center !p-1">Measurement:</div>
    </div>

    <div class="description-grid-row grid description-grid !border-t-0 !min-h-[12rem]">
        <!-- Container -->
        <div class="!border-b-0">
            <div class="font-normal text-[0.7rem] mb-1"> {{strtoupper($shipment->container_no) ?? '-'}} </div>
            <div class="bl-content-text text-sm">
                SMBB0184<br>
                SEAL: {{strtoupper($shipment->seal_number) ?? '-'}} <br>
            </div>
            <div class="font-normal text-[0.7rem] text-gray-500 mt-2">COPY</div>
        </div>

        <!-- Description -->
        <div class="!border-b-0">
            <div class="bl-content-text text-sm mb-2">
                1 CONT. 40'X9'6" HIGH CUBE CONT. SLAC*
            </div>
            <pre class="font-normal text-xs leading-tight">
                {{strtoupper($shipment->goods_description) ?? '-'}}
            </pre>
            <div class="bl-content-text text-center text-sm font-bold mt-2">
                <span class="border-t border-black px-4">139 BOX</span>
            </div>
        </div>

        <!-- Weight -->
        <div class="text-right !border-b-0">
            <div class="bl-content-text text-sm mt-10">10000.000 KGS</div>
        </div>

        <!-- Measurement -->
        <div class="!border-b-0"></div>
    </div>
    
    <!-- Footer Totals and Movement -->
    <div class="grid grid-cols-4 border-l border-r border-b border-black -mt-[1px]">
        <!-- Movement -->
        <div class="col-span-2 bl-box !border-r-0 !border-b-0">
            <div class="bl-box-header">Movement:</div>
            <div class="bl-content-text">FCL/FCL</div>
        </div>
        
        <!-- Total Containers/Packages -->
        <div class="col-span-2 grid grid-rows-2">
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Total No. of Containers received by the Carrier:</div>
                <div class="bl-content-text"> {{strtoupper($shipment->total_containers) ?? '-'}} </div>
            </div>
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Packages received by the Carrier.</div>
                <div class="bl-content-text"> {{strtoupper($shipment->total_packages) ?? '-'}} </div>
            </div>
        </div>

        <!-- Freight Charges and Statement -->
        <div class="col-span-4 grid grid-cols-6">
            <div class="col-span-4 p-2 text-xs font-normal">
                <div class="font-semibold text-sm mb-1">RECEIVED by the Carrier from the Shipper in apparent good order...</div>
                Above Particulars as declared by Shipper. Without responsibility or warranty as to correctness by Carrier [see clause 11]
                <br>
                <div class="text-right text-gray-500 mt-1">COPY</div>
            </div>
            
            <div class="col-span-2 p-1 text-xs border-l border-black flex flex-col justify-end">
                <div class="flex justify-between font-bold border-b border-gray-400 mb-0.5">
                    <span>Total Freight Prepaid</span>
                    <span>$X,XXX.XX</span>
                </div>
                <div class="flex justify-between font-bold border-b border-gray-400 mb-0.5">
                    <span>Total Freight Collect</span>
                    <span>$X,XXX.XX</span>
                </div>
                <div class="flex justify-between font-bold">
                    <span>Total Freight</span>
                    <span>$X,XXX.XX</span>
                </div>
            </div>
        </div>

        <!-- Place and Date of Issue -->
        <div class="col-span-4 grid grid-cols-3 border-t border-black -mt-[1px]">
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Place and date of issue:</div>
                <div class="bl-content-text">ATLANTA, GA <span class="ml-4">AUG/15/2025</span></div>
            </div>
            <div class="bl-box !border-r-0 !border-b-0">
                <div class="bl-box-header">Freight payable at:</div>
                <div class="bl-content-text">ORIGIN</div>
            </div>
            <div class="bl-box !border-b-0">
                <div class="bl-box-header">Number of original Bs/L:</div>
                <div class="bl-content-text">3</div>
            </div>
        </div>
        
        <!-- Carrier Signature Box -->
        <div class="col-span-4 bl-box">
            <div class="bl-box-header">FOR ABOVE NAMED CARRIER</div>
            <div class="bl-content-text font-normal pt-2">HAPAG-LLOYD (AMERICA) LLC(AS AGENT) MTD17312 (FB::::)</div>
        </div>
    </div>
    
</div>

</body>
</html>
