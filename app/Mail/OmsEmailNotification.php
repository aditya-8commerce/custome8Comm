<?php
namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class OmsEmailNotification extends Mailable {
 
    use Queueable, SerializesModels;
		
	public $subject = '';
	public $content = '';
	public $attachments = array();
	
	public function __construct($subject, $content , $attachments=array()) {
		$this->subject      = $subject;
		$this->content      = $content;
		$this->attachments  = $attachments;
	}
	
    //build the message.
    public function build() {
        $mail = $this->subject($this->subject)->view('emails.index')->with(['content' => $this->content]);
		
		if(count($this->attachments) > 0){
			foreach ( $this->attachments as $attachment ) {
				$mail->attach($attachment['file'],$attachment['options']);
			}
		}
		
		return $mail;
    }
}