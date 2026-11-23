# Trace Chile Expeditions

Primera versión de un sitio de turismo en Laravel 12 y Livewire 3. Compras de paquetes **sin fecha**, con coordinación posterior entre la agencia y el cliente.

## Estado de la entrega

Proyecto verificado en desarrollo local: compilación Vite correcta y 13 pruebas funcionales aprobadas (62 aserciones). Incluye `composer.lock` y `package-lock.json` para instalar las versiones verificadas.

El repositorio no incluye `.env`, bases de datos locales, credenciales ni dependencias instaladas. Copiar `.env.example` y generar una clave propia antes de iniciar.

## Qué incluye

- Portada editorial, catálogo con búsqueda y filtros por destino y actividad, orden por precio CLP o duración y paginación.
- Fichas con descripción, duración, dificultad, itinerario, galería ampliable, servicios y condiciones.
- Formulario de viajes personalizados: las consultas quedan en el administrador.
- Compra sin fecha para 1 a 20 participantes. CLP con Flow y USD con PayPal; precios independientes por persona, sin conversión automática.
- Integración de inicio, retorno y verificación de pagos. El navegador nunca fija el importe; se calcula desde la base de datos y se verifica contra la pasarela.
- Confirmación idempotente del pago, enlace firmado de consulta y correo de recepción de pago. El estado pagado significa **pendiente de coordinación**, no fecha confirmada.
- Reconciliación programada de pagos para recuperar confirmaciones pendientes y reintentar correos.
- Administrador propio en Livewire: autenticación, resumen, creación, edición, duplicación, publicación y retiro de paquetes; precios, itinerarios, portada y galería ordenable; compras y consultas.
- Sin registro público de usuarios, contraseñas predeterminadas ni captura de tarjetas en el sitio.
- Diseño responsive con Vite para compilar CSS/JS y recarga automática durante desarrollo.

## Preparar el entorno de pruebas

Node.js 20.19+ o 22.12+ y npm para compilar recursos. PHP 8.2 o superior con extensiones necesarias para Laravel, incluyendo PDO SQLite (o PDO MySQL), mbstring, openssl, curl, fileinfo, dom y xml. Composer 2, acceso a Packagist y permisos de escritura en `storage` y `bootstrap/cache`. El documento raíz del servidor debe ser `public/`.

Ejecutar desde esta carpeta:

```sh
cp .env.example .env
composer install
php artisan key:generate
```

Crear el archivo vacío `database/database.sqlite` si se usa SQLite. Con MySQL, ajustar las variables `DB_*` en `.env` y crear la base de datos previamente.

```sh
php artisan migrate --seed
php artisan storage:link
php artisan trace:admin
php artisan test
php artisan serve
```

En otra terminal, desde la misma carpeta:

```sh
npm install
npm run dev
```

Como alternativa a mantener Vite en ejecución, usar `npm run build` para generar los recursos antes de abrir la web.

En producción, ejecutar `npm run build` y publicar `public/build` junto con la aplicación. Editar los estilos en `resources/css/app.css` y el JavaScript en `resources/js/app.js`. Livewire incluye Alpine; no se importa una segunda copia. `public/site.css` se conserva solo para la vista previa HTML independiente. Las pruebas PHP deshabilitan Vite para no depender del manifiesto compilado.

Abrir `http://localhost:8000`. Administrador: `http://localhost:8000/admin`. El comando `trace:admin` solicita nombre, correo y contraseña de al menos 12 caracteres; no crea usuarios por defecto. No ejecutar migraciones destructivas en una base existente.

Para formatear el PHP cuando esté instalado Composer:

```sh
vendor/bin/pint
```

La vista previa `public/design-preview.html` se abre directamente en el navegador sin PHP. Solo muestra la portada: sus búsquedas, enlaces y precios son ilustrativos. Las funciones reales están implementadas en Laravel, no en este archivo.

## Flow y PayPal: pruebas sandbox

1. Configurar las credenciales **sandbox** en `.env`. Los endpoints de ejemplo ya apuntan a sandbox.
2. Configurar `APP_URL` con un dominio HTTPS público de pruebas. Flow necesita llamar al servidor; `localhost` no es accesible desde sus servidores. El retorno de Flow es POST y no depende de la cookie del comprador.
3. Establecer `PAYMENTS_ENABLED=true` únicamente para comenzar las pruebas de las pasarelas. Con `false`, ningún checkout inicia un pago, incluso si hay credenciales.
4. Mantener una tarifa CLP para mostrar Flow y una tarifa USD para mostrar PayPal; si una tarifa está vacía, ese medio no aparece para ese paquete. Son precios finales por persona definidos por la agencia.
5. Probar pago exitoso, rechazo, abandono, retorno repetido, callback duplicado y retorno interrumpido. Revisar la orden en la pasarela, el total, la moneda, el estado en `/admin/reservas` y el correo.
6. Para probar correo real, configurar SMTP. `MAIL_MAILER=log` solo escribe el correo en `storage/logs/laravel.log` y no lo entrega al cliente.

Rutas de integración:

- `POST /payments/flow/confirmation`: consulta el token con firma HMAC y verifica referencia, orden, moneda e importe antes de marcar pagado.
- `GET|POST /payments/flow/return`: consulta el estado y redirige a una URL firmada de compra.
- `GET /payments/paypal/{booking}/return`: verifica la URL firmada, permite los parámetros `token` y `PayerID` añadidos por PayPal, verifica la orden, captura desde el servidor y valida la captura.
- `GET /compra/{booking}`: requiere firma válida; no permite consultar datos de otra compra enumerando identificadores.

No se implementó webhook PayPal: la recuperación de pagos completados sin retorno se hace consultando Orders API desde el reconciliador. Los reembolsos se gestionan directamente en Flow/PayPal en esta versión; no hay interfaz de devoluciones.

Ejecutar el reconciliador manualmente durante pruebas:

```sh
php artisan trace:reconcile-payments
```

En un servidor, configurar el scheduler de Laravel para ejecutar `php artisan schedule:run` cada minuto desde la carpeta del proyecto; el comando de reconciliación se programa cada diez minutos. La idempotencia impide cambiar una compra pagada nuevamente a pendiente por una notificación repetida. Los fallos de correo se registran y se reintentan. Para producción con varios workers, centralizar caché/bloqueos y preferir MySQL/PostgreSQL para concurrencia.

## Antes de publicar comercialmente

Los tres paquetes sembrados, sus itinerarios, servicios y precios son **ejemplos de demostración**, no ofertas reales. Las fotografías externas son imágenes de inspiración y no representan necesariamente los destinos anunciados. Reemplazarlas por fotografías autorizadas y específicas de cada viaje, subidas desde el administrador. El fondo SVG local permite que el diseño siga visible si no hay conexión. Las fuentes tienen alternativas del sistema si Google Fonts no está disponible.

La marca del encabezado es una interpretación vectorial provisional inspirada en la referencia; sustituir el SVG de `resources/views/components/brand.blade.php` por el archivo oficial del logo proporcionado por la agencia.

Completar información de contacto (`CONTACT_EMAIL`, `CONTACT_WHATSAPP`), razón social, RUT, domicilio y política de privacidad. Validar las condiciones de cada paquete: vigencia, coordinación, disponibilidad, cambios, cancelaciones y devoluciones. Los textos legales incluidos son preliminares y están identificados como tales. No se inventaron reseñas, certificaciones ni cifras de clientes.

Usar credenciales de producción solo después de las pruebas. Establecer `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS y `APP_URL` definitivo. Revisar correo y scheduler, conservar `.env` fuera del control de versiones y realizar copias de seguridad de base de datos e imágenes.

La galería acepta hasta 20 imágenes JPG, PNG o WebP, con un máximo de 8 MB cada una. Ajustar `upload_max_filesize`, `post_max_size`, límites del servidor y de Livewire si se suben varias imágenes simultáneamente. Las imágenes retiradas de un paquete no se borran físicamente de inmediato, para no romper copias del paquete que compartan archivos; puede programarse posteriormente una limpieza de archivos huérfanos.

## Referencias

- https://www.swoop-patagonia.com/ — referencia principal de organización de viajes y exploración.
- https://chilenativo.travel/es/ — destinos, actividades y viajes personalizados.
- https://walk-trek.tur.ar/es/ — estructura de programas y filtros.
- https://livewire.laravel.com/docs/3.x/installation
- https://developers.flow.cl/api
- https://developer.paypal.com/studio/checkout/standard/integrate

Diseño y textos originales inspirados en estas referencias, sin copiar sus contenidos.
