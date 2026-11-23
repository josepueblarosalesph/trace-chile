<?php
namespace App\Services;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
class FlowGateway {
 private function request(string $method,string $path,array $params): array {
  $params['apiKey']=config('services.flow.key');ksort($params);
  $toSign='';foreach($params as $key=>$value)$toSign.=$key.$value;
  $params['s']=hash_hmac('sha256',$toSign,config('services.flow.secret'));
  $http=Http::acceptJson()->timeout(25)->connectTimeout(10);
  $url=rtrim(config('services.flow.url'),'/').'/'.$path;
  return ($method==='GET'?$http->get($url,$params):$http->asForm()->post($url,$params))->throw()->json();
 }
 public function create(Booking $b): array {return $this->request('POST','payment/create',[
  'commerceOrder'=>$b->id,'subject'=>'Trace Chile: '.$b->package_title,'currency'=>'CLP','amount'=>$b->total_amount,'email'=>$b->email,
  'urlConfirmation'=>route('flow.confirmation'),'urlReturn'=>route('flow.return'),
 ]);}
 public function status(string $token): array {return $this->request('GET','payment/getStatus',['token'=>$token]);}
}
