<?php
namespace App\Livewire\Admin;
use App\Models\Package;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
class PackageEditor extends Component {
 use WithFileUploads;
 #[Locked] public ?int $packageId=null;
 #[Locked] public array $existingGallery=[];
 #[Locked] public ?string $existingCover=null;
 public string $title='',$slug='',$destination='Torres del Paine',$activity='Trekking',$difficulty='Moderada',$summary='',$description='',$included='',$excluded='',$technical_details='',$terms='';
 public int $days=1;
 public $price_clp=null,$price_usd=null,$coverUpload=null;
 public array $galleryUploads=[],$itinerary=[];
 public bool $published=false,$featured=false;
 public function mount(?Package $package=null){
  abort_unless(auth()->user()?->is_admin,403);
  if($package?->exists){$this->packageId=$package->id;foreach(['title','slug','destination','activity','difficulty','summary','description','included','excluded','technical_details','terms','days','published','featured','price_clp'] as $key)$this->$key=$package->$key??($key==='price_clp'?null:'');$this->price_usd=$package->price_usd_cents?number_format($package->price_usd_cents/100,2,'.',''):null;$this->itinerary=$package->itinerary??[];$this->existingGallery=$package->gallery??[];$this->existingCover=$package->cover;}
 }
 public function addDay(){$this->itinerary[]=['title'=>'','description'=>''];}
 public function removeDay(int $index){unset($this->itinerary[$index]);$this->itinerary=array_values($this->itinerary);}
 public function removeImage(int $index){unset($this->existingGallery[$index]);$this->existingGallery=array_values($this->existingGallery);}
 public function moveImage(int $index,int $direction){$target=$index+$direction;if(isset($this->existingGallery[$index],$this->existingGallery[$target]))[$this->existingGallery[$index],$this->existingGallery[$target]]=[$this->existingGallery[$target],$this->existingGallery[$index]];}
 public function save(){
  abort_unless(auth()->user()?->is_admin,403);
  $this->slug=Str::slug($this->slug?:$this->title);
  $data=$this->validate([
   'title'=>'required|string|max:160','slug'=>['required','max:180','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',Rule::unique('packages','slug')->ignore($this->packageId)],
   'destination'=>'required|string|max:100','activity'=>'required|string|max:80','difficulty'=>'required|string|max:80','days'=>'required|integer|min:1|max:90','summary'=>'required|string|max:500','description'=>'required|string|max:30000',
   'included'=>'nullable|string|max:15000','excluded'=>'nullable|string|max:15000','technical_details'=>'nullable|string|max:15000','terms'=>'nullable|string|max:15000',
   'price_clp'=>'nullable|integer|min:1|max:100000000','price_usd'=>'nullable|numeric|min:0.01|max:100000|decimal:0,2','published'=>'boolean','featured'=>'boolean',
   'itinerary'=>'array|max:90','itinerary.*.title'=>'required|string|max:180','itinerary.*.description'=>'required|string|max:6000',
   'coverUpload'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:8192','galleryUploads'=>'array|max:20','galleryUploads.*'=>'image|mimes:jpg,jpeg,png,webp|max:8192',
  ]);
  if($this->published&&!$this->price_clp&&!$this->price_usd){$this->addError('price_clp','Define al menos un precio para publicar.');return;}
  if(count($this->existingGallery)+count($this->galleryUploads)>20){$this->addError('galleryUploads','Máximo 20 imágenes de galería.');return;}
  unset($data['coverUpload'],$data['galleryUploads'],$data['price_usd']);
  $data['price_clp']=$this->price_clp?:null;$data['price_usd_cents']=$this->price_usd?(int)round((float)$this->price_usd*100):null;
  $data['cover']=$this->coverUpload?$this->coverUpload->store('packages','public'):$this->existingCover;
  $data['gallery']=$this->existingGallery;foreach($this->galleryUploads as $image)$data['gallery'][]=$image->store('packages','public');
  Package::updateOrCreate(['id'=>$this->packageId],$data);
  session()->flash('success','Paquete guardado.');return redirect()->route('admin.packages');
 }
 public function render(){return view('livewire.admin.package-editor')->layout('components.layouts.admin',['title'=>$this->packageId?'Editar paquete':'Nuevo paquete']);}
}
