<h1>Trace Chile Expeditions</h1>
<p>Hola {{ $booking->name }}, recibimos tu pago por <strong>{{ $booking->package_title }}</strong>.</p>
<p>Participantes: {{ $booking->travelers }} · Total: {{ $booking->display_total }}</p>
<p>Referencia: {{ $booking->id }}</p>
<p><strong>El paquete se compró sin fecha.</strong> Nos pondremos en contacto contigo para coordinar la fecha, disponibilidad y detalles del viaje. La compra no confirma una salida específica.</p>
<p><a href="{{ URL::temporarySignedRoute('payment.status',now()->addDays(30),['booking'=>$booking->id]) }}">Consultar compra</a></p>
