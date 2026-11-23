<?php
namespace Tests\Feature;
use App\Livewire\{Checkout,Contact};
use App\Livewire\Admin\PackageEditor;
use App\Models\{Booking,Package,User};
use App\Services\{FlowGateway,PaymentService,PaypalGateway};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,Mail,URL};
use Livewire\Livewire;
use Tests\TestCase;
class StorefrontTest extends TestCase {
 use RefreshDatabase;
 private function trip(array $changes=[]): Package {return Package::create(array_merge(['title'=>'Un viaje al Paine','slug'=>'un-viaje-al-paine','destination'=>'Torres del Paine','activity'=>'Trekking','days'=>5,'difficulty'=>'Moderada','summary'=>'Descripción de ejemplo','description'=>'Un viaje único.','price_clp'=>100000,'price_usd_cents'=>12000,'published'=>true],$changes));}
 private function order(array $changes=[]): Booking {return Booking::create(array_merge(['package_id'=>$this->trip()->id,'package_title'=>'Un viaje al Paine','name'=>'Ana','email'=>'ana@example.com','phone'=>'+56912345678','travelers'=>2,'provider'=>'flow','currency'=>'CLP','unit_amount'=>100000,'total_amount'=>200000,'provider_order_id'=>'1234','provider_token'=>'test-token'],$changes));}
 public function test_public_pages_render_and_private_packages_are_hidden(): void {
  $p=$this->trip(['published'=>false]);$this->get('/')->assertOk();$this->get('/viajes')->assertOk();$this->get('/viaje-a-medida')->assertOk();$this->get('/nosotros')->assertOk();$this->get('/condiciones')->assertOk();$this->get('/privacidad')->assertOk();$this->get('/admin/login')->assertOk();$this->get('/viajes/'.$p->slug)->assertNotFound();$this->get('/viajes/'.$p->slug.'/comprar')->assertNotFound();
  $p->update(['published'=>true]);$this->get('/viajes/'.$p->slug)->assertOk()->assertSee('Coordinamos tu viaje');$this->get('/viajes/'.$p->slug.'/comprar')->assertOk();
 }
 public function test_admin_requires_an_administrator(): void {
  $this->get('/admin/paquetes')->assertRedirect('/admin/login');
  $user=User::create(['name'=>'Visitante','email'=>'visitor@example.com','password'=>'long-password','is_admin'=>false]);
  $this->actingAs($user)->get('/admin/paquetes')->assertForbidden();
 }
 public function test_admin_can_render_pages_and_create_a_package(): void {
  $user=User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>'long-password','is_admin'=>true]);$this->actingAs($user);
  foreach(['/admin','/admin/paquetes','/admin/paquetes/crear','/admin/reservas','/admin/consultas'] as $url)$this->get($url)->assertOk();
  Livewire::test(PackageEditor::class)->set('title','Nuevo paquete')->set('summary','Una aventura')->set('description','Descripción del programa')->set('price_clp',150000)->set('published',true)->call('save')->assertHasNoErrors()->assertRedirect(route('admin.packages'));
  $this->assertDatabaseHas('packages',['slug'=>'nuevo-paquete','price_clp'=>150000,'published'=>true]);
 }
 public function test_checkout_keeps_identifier_locked(): void {
  $p=$this->trip();$this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
  Livewire::test(Checkout::class,['package'=>$p])->set('packageId',999);
 }
 public function test_checkout_uses_database_price_and_has_no_date(): void {
  config(['services.flow.key'=>'test-key','services.flow.secret'=>'test-secret']);
  $p=$this->trip();
  $service=$this->mock(PaymentService::class);$service->shouldReceive('configured')->with('flow')->andReturn(true);
  $service->shouldReceive('start')->once()->withArgs(fn(Booking $b)=>$b->total_amount===300000&&$b->currency==='CLP'&&$b->travelers===3)->andReturn('https://sandbox.flow.cl/pay');
  Livewire::test(Checkout::class,['package'=>$p])->set('name','Ana')->set('email','ana@example.com')->set('phone','+56912345678')->set('travelers',3)->set('acceptTerms',true)->call('submit')->assertHasNoErrors()->assertRedirect('https://sandbox.flow.cl/pay');
  $this->assertDatabaseHas('bookings',['total_amount'=>300000,'unit_amount'=>100000,'status'=>'pending']);
 }
 public function test_checkout_requires_terms_and_enabled_gateway(): void {
  $p=$this->trip();config(['services.payments_enabled'=>false]);
  Livewire::test(Checkout::class,['package'=>$p])->set('name','Ana')->set('email','ana@example.com')->set('phone','123')->call('submit')->assertHasErrors(['acceptTerms']);
  Livewire::test(Checkout::class,['package'=>$p])->set('name','Ana')->set('email','ana@example.com')->set('phone','123')->set('acceptTerms',true)->call('submit')->assertHasErrors(['payment']);
  $this->assertDatabaseCount('bookings',0);
 }
 public function test_flow_confirmation_verifies_amount_and_is_idempotent(): void {
  Mail::fake();$b=$this->order();
  $this->mock(FlowGateway::class)->shouldReceive('status')->twice()->with('test-token')->andReturn(['commerceOrder'=>$b->id,'flowOrder'=>1234,'currency'=>'CLP','amount'=>200000,'status'=>2]);
  $this->post('/payments/flow/confirmation',['token'=>'test-token'])->assertOk();$this->post('/payments/flow/confirmation',['token'=>'test-token'])->assertOk();
  $this->assertNotNull($b->fresh()->paid_at);$this->assertSame('paid',$b->fresh()->status);Mail::assertSent(\App\Mail\BookingReceipt::class,1);
 }
 public function test_flow_rejects_a_mismatched_amount(): void {
  $b=$this->order();$this->mock(FlowGateway::class)->shouldReceive('status')->once()->andReturn(['commerceOrder'=>$b->id,'flowOrder'=>1234,'currency'=>'CLP','amount'=>1000,'status'=>2]);
  try{app(PaymentService::class)->reconcileFlow('test-token');$this->fail('Payment should be rejected');}catch(\RuntimeException $e){$this->assertSame('pending',$b->fresh()->status);$this->assertNull($b->fresh()->paid_at);}
 }
 public function test_paypal_rejects_completed_order_with_wrong_currency(): void {
  $b=$this->order(['provider'=>'paypal','currency'=>'USD','unit_amount'=>10000,'total_amount'=>20000]);
  $data=['id'=>'1234','status'=>'COMPLETED','purchase_units'=>[['custom_id'=>$b->id,'payments'=>['captures'=>[['id'=>'capture-1','status'=>'COMPLETED','amount'=>['currency_code'=>'EUR','value'=>'200.00']]]]]]];
  $this->mock(PaypalGateway::class)->shouldReceive('status')->once()->andReturn($data);
  try{app(PaymentService::class)->reconcilePaypal($b);$this->fail('Payment should be rejected');}catch(\RuntimeException $e){$this->assertNull($b->fresh()->paid_at);}
 }
 public function test_paypal_verified_capture_marks_paid_only_once(): void {
  Mail::fake();$b=$this->order(['provider'=>'paypal','currency'=>'USD','unit_amount'=>10000,'total_amount'=>20000]);
  $data=['id'=>'1234','status'=>'COMPLETED','purchase_units'=>[['custom_id'=>$b->id,'payments'=>['captures'=>[['id'=>'capture-1','status'=>'COMPLETED','amount'=>['currency_code'=>'USD','value'=>'200.00']]]]]]];
  $this->mock(PaypalGateway::class)->shouldReceive('status')->once()->andReturn($data);
  app(PaymentService::class)->capturePaypal($b);app(PaymentService::class)->capturePaypal($b->fresh());$this->assertSame('paid',$b->fresh()->status);Mail::assertSent(\App\Mail\BookingReceipt::class,1);
 }
 public function test_booking_details_require_a_signed_link(): void {
  $b=$this->order();$this->get('/compra/'.$b->id)->assertForbidden();$this->get(URL::temporarySignedRoute('payment.status',now()->addHour(),['booking'=>$b->id]))->assertOk()->assertSee('Pendiente de pago');
 }
 public function test_inquiries_are_saved_and_script_text_is_escaped(): void {
  Livewire::test(Contact::class)->set('name','Ana')->set('email','ana@example.com')->set('message','Quiero viajar a la Patagonia con mi familia.')->call('submit')->assertSet('sent',true);
  $this->assertDatabaseCount('inquiries',1);$p=$this->trip(['description'=>'<script>alert(1)</script>']);$this->get('/viajes/'.$p->slug)->assertDontSee('<script>alert(1)</script>',false)->assertSee('&lt;script&gt;',false);
 }
 public function test_paypal_return_accepts_appended_gateway_parameters_and_checks_order(): void {
  Mail::fake();$b=$this->order(['provider'=>'paypal','currency'=>'USD','unit_amount'=>10000,'total_amount'=>20000]);
  $data=['id'=>'1234','status'=>'COMPLETED','purchase_units'=>[['custom_id'=>$b->id,'payments'=>['captures'=>[['id'=>'capture-return','status'=>'COMPLETED','amount'=>['currency_code'=>'USD','value'=>'200.00']]]]]]];
  $this->mock(PaypalGateway::class)->shouldReceive('status')->once()->andReturn($data);
  $url=URL::temporarySignedRoute('paypal.return',now()->addHour(),['booking'=>$b->id]);
  $this->get($url.'&token=wrong&PayerID=test')->assertForbidden();
  $this->get($url.'&token=1234&PayerID=test')->assertRedirect();
  $this->assertSame('paid',$b->fresh()->status);
 }

}
