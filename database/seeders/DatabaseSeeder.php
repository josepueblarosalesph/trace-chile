<?php
namespace Database\Seeders;
use App\Models\Package;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  // Demonstration content only; never overwrite packages already edited by the agency.
  $trips=[
   ['title'=>'Circuito W: el corazón del Paine','slug'=>'circuito-w-corazon-del-paine','destination'=>'Torres del Paine','activity'=>'Trekking','days'=>5,'difficulty'=>'Moderada','summary'=>'Cinco días entre cumbres de granito, lagos turquesa y el silencio que solo existe aquí.','cover'=>'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1000&q=85','price_clp'=>1290000,'price_usd_cents'=>149000],
   ['title'=>'Patagonia a tu ritmo','slug'=>'patagonia-a-tu-ritmo','destination'=>'Carretera Austral','activity'=>'Ruta escénica','days'=>7,'difficulty'=>'Fácil','summary'=>'Bosques, agua y caminos abiertos. Una invitación a descubrir el sur sin mirar el reloj.','cover'=>'https://images.unsplash.com/photo-1472396961693-142e6e269027?auto=format&fit=crop&w=1000&q=85','price_clp'=>890000,'price_usd_cents'=>105000],
   ['title'=>'Tras la huella del Fitz Roy','slug'=>'huella-del-fitz-roy','destination'=>'El Chaltén','activity'=>'Trekking','days'=>4,'difficulty'=>'Exigente','summary'=>'Senderos que se abren entre bosques y montañas. Acércate a uno de los grandes íconos del sur.','cover'=>'https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=1000&q=85','price_clp'=>790000,'price_usd_cents'=>92000],
  ];
  foreach($trips as $trip)Package::firstOrCreate(['slug'=>$trip['slug']],array_merge($trip,[
   'description'=>'Contenido de demostración. Este programa y sus precios son referenciales para revisar el diseño y no constituyen una oferta comercial. Reemplazar con los servicios reales de Trace Chile Expeditions antes de publicar.',
   'included'=>"Acompañamiento de guía (ejemplo)\nTraslados indicados en el programa (ejemplo)",
   'excluded'=>"Pasajes aéreos\nServicios no descritos en la propuesta final",'technical_details'=>'Completar distancias, desniveles, requisitos físicos y equipamiento para este programa.',
   'terms'=>'PAQUETE DE DEMOSTRACIÓN. No habilitar pagos reales. Definir vigencia, cambios, cancelaciones y condiciones de coordinación de fecha antes de comercializar.',
   'itinerary'=>[['title'=>'Bienvenidos al sur','description'=>'Encuentro y presentación del recorrido. Contenido de demostración.'],['title'=>'Un camino por descubrir','description'=>'Jornada de exploración. Reemplazar con el itinerario real.']],
   'gallery'=>[$trip['cover']],'featured'=>true,'published'=>true,
  ]));
 }
}
