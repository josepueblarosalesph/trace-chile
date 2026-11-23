<?php
return ['name'=>env('APP_NAME','Trace Chile Expeditions'),'env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost:8000'),'timezone'=>'America/Santiago','locale'=>'es','fallback_locale'=>'en','faker_locale'=>'es_CL','cipher'=>'AES-256-CBC','key'=>env('APP_KEY'),'previous_keys'=>[],'maintenance'=>['driver'=>'file']];
