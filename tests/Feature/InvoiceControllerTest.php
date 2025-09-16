<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\Invoice;

class InvoiceControllerTest extends TestCase
{
    /** @test */
    public function business_admin_can_view_invoices()
    {
        $user = User::whereHas('roles', function ($query) {
            $query->where('name', 'businessadministrator');
        })->first();

        $branch = $user->branch;

   
        //dd($branch);

        $invoice = Invoice::where('branch_id', $branch->id)->first();
        //dd($invoice);

        $response = $this->actingAs($user)->getJson(route('invoices.show', $invoice->id));

        $response->assertStatus(200)
                 ->assertJsonStructure(['invoice']);
    }
}
