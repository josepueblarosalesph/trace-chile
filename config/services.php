<?php
return [
'payments_enabled'=>(bool)env('PAYMENTS_ENABLED',false),
'flow'=>['key'=>env('FLOW_API_KEY'),'secret'=>env('FLOW_SECRET_KEY'),'url'=>env('FLOW_API_URL','https://sandbox.flow.cl/api')],
'paypal'=>['client_id'=>env('PAYPAL_CLIENT_ID'),'secret'=>env('PAYPAL_CLIENT_SECRET'),'url'=>env('PAYPAL_API_URL','https://api-m.sandbox.paypal.com')],
'contact'=>['email'=>env('CONTACT_EMAIL'),'whatsapp'=>env('CONTACT_WHATSAPP')],
];
