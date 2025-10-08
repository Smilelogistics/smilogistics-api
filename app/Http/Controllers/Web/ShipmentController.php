<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    // public function index()
    // {
    //     return view('shipments.index');
    // }


        public function test1()
{
    $branch = [
        'user' => [
            'fname' => 'John',
            'lname' => 'Doe',
            'email' => 'JOHN.DOE@SMILESLOGISTICS.COM',
        ],
        'address' => '2646 HIGHWAY AVE',
        'company_name' => 'SMILES LOGISTICS GROUP',
        'company_address' => '2646 HIGHWAY AVE',
        'company_city' => 'HIGHLAND, INDIANA 46322',
        'phone' => '+1 (317) 555-7890',
    ];

    $shipment = [
        'carrier' => [
            'name' => 'Hapag-Lloyd',
        ],
        
    ];

    return view('test', compact('shipment', 'branch'));
}

public function test()
{
    $branch = (object) [
        'user' => (object) [
            'fname' => 'John',
            'lname' => 'Doe',
            'email' => 'JOHN.DOE@SMILESLOGISTICS.COM',
        ],
        'address' => '2646 HIGHWAY AVE',
        'company_name' => 'SMILES LOGISTICS GROUP',
        'company_address' => '2646 HIGHWAY AVE',
        'company_city' => 'HIGHLAND, INDIANA 46322',
        'phone' => '+1 (317) 555-7890',
    ];

    $shipment = (object) [
        'carrier' => (object) [
            'name' => 'Hapag-Lloyd',
        ],
        'shipper_name' => 'SMILES LOGISTICS GROUP',
        'ocean_shipper_reference_number' => 'SMG-USA-2025-001',
        'consignee' => 'MR. OLUWASEUN ADEMOLA',
        'consignee_phone' => '+234 806 123 4567',
        'consignee_email' => 'OLUWASEUN.ADEMOLA@GMAIL.COM',
        'branch' => $branch,
        'notify_party_name' => 'ADEBAYO LOGISTICS LTD.',
        'notify_party_phone' => '+234 705 987 6543',
        'notify_party_email' => 'INFO@ADEBAYOLOGISTICS.COM',
        'vessel_aircraft_name' => 'M/V HAPAG SPIRIT',
        'port_of_loading' => 'HOUSTON, TX',
        'voyage_number' => 'HLU9876',
        'place_of_delivery' => 'LAGOS, NIGERIA',
        'port_of_discharge' => 'TIN CAN ISLAND PORT, LAGOS',
        'container_no' => 'HLXU1234567',
        'seal_number' => 'SEAL9087',
        'goods_description' => "USED HOUSEHOLD GOODS AND PERSONAL EFFECTS\n1X40’ HIGH CUBE CONTAINER\nSHIPPER'S LOAD AND COUNT\nSAID TO CONTAIN: FURNITURE, CLOTHING, APPLIANCES",
        'total_containers' => '1X40HC',
        'total_packages' => '139 BOXES',
    ];

    return view('test', compact('shipment', 'branch'));
}
}

