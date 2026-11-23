<?php
return ['default'=>'local','disks'=>['local'=>['driver'=>'local','root'=>storage_path('app/private'),'throw'=>false],'public'=>['driver'=>'local','root'=>storage_path('app/public'),'url'=>env('APP_URL','http://localhost:8000').'/storage','visibility'=>'public','throw'=>true]],'links'=>[public_path('storage')=>storage_path('app/public')]];
