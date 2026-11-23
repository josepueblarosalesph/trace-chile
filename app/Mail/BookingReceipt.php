<?php
namespace App\Mail;
use App\Models\Booking;
use Illuminate\Mail\Mailable;
class BookingReceipt extends Mailable {
 public function __construct(public Booking $booking,public bool $adminCopy=false){}
 public function build(){return $this->subject(($this->adminCopy?'Nueva compra: ':'Recibimos tu pago: ').$this->booking->package_title)->view('emails.booking');}
}
