<?php
namespace App\Livewire\Admin;
use App\Models\Package;
use Livewire\Component;
use Livewire\WithPagination;
class Packages extends Component {
 use WithPagination;
 public string $search='';
 public function mount(){abort_unless(auth()->user()?->is_admin,403);}
 public function updatedSearch(){$this->resetPage();}
 public function toggle(int $id){abort_unless(auth()->user()?->is_admin,403);$p=Package::findOrFail($id);if(!$p->published&&!$p->price_clp&&!$p->price_usd_cents){session()->flash('success','Edita el paquete y define un precio antes de publicarlo.');return;}$p->update(['published'=>!$p->published]);}
 public function duplicate(int $id){abort_unless(auth()->user()?->is_admin,403);$p=Package::findOrFail($id);$copy=$p->replicate();$copy->title=$p->title.' (copia)';$copy->slug=$p->slug.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));$copy->published=false;$copy->featured=false;$copy->save();return redirect()->route('admin.packages.edit',$copy);}
 public function render(){return view('livewire.admin.packages',['packages'=>Package::where('title','like','%'.$this->search.'%')->latest()->paginate(12)])->layout('components.layouts.admin',['title'=>'Paquetes']);}
}
