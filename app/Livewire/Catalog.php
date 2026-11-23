<?php
namespace App\Livewire;
use App\Models\Package;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
class Catalog extends Component {
 use WithPagination;
 #[Url] public string $destination='';
 #[Url] public string $activity='';
 #[Url] public string $search='';
 #[Url] public string $sort='featured';
 public function updated($property){if(in_array($property,['destination','activity','search','sort']))$this->resetPage();}
 public function clearFilters(){ $this->reset('destination','activity','search','sort');$this->resetPage();}
 public function render(){
  $query=Package::where('published',true)
   ->when($this->destination,fn($q)=>$q->where('destination',$this->destination))
   ->when($this->activity,fn($q)=>$q->where('activity',$this->activity))
   ->when($this->search,fn($q)=>$q->where(fn($q)=>$q->where('title','like','%'.$this->search.'%')->orWhere('summary','like','%'.$this->search.'%')));
  match($this->sort){'price'=>$query->orderByRaw('price_clp IS NULL')->orderBy('price_clp'),'duration'=>$query->orderBy('days'),default=>$query->orderByDesc('featured')->latest()};
  return view('livewire.catalog',['packages'=>$query->paginate(9),'destinations'=>Package::where('published',true)->distinct()->orderBy('destination')->pluck('destination'),'activities'=>Package::where('published',true)->distinct()->orderBy('activity')->pluck('activity')])->layout('components.layouts.app',['title'=>'Explora la Patagonia']);
 }
}
