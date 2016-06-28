<?php
class Mail
{
	public $fromAddress;
	public $fromName;
	public $subject;
	public $bodyTxt;
	public $bodyHtml;

	private $mailTo = array();
	private $mailCC = array();
	private $mailBCC = array();
	private $attachments = array();

	function __construct()
	{
		$this->fromName = \Config::getCONFIG()->get("system_title");
		$this->fromAddress = \Config::getCONFIG()->get("system_email");
	}

	function addRecipient($address)
	{
		$this->mailTo[] = $address;
	}

	function addCC($address)
	{
		$this->mailCC[] = $address;
	}

	function addBCC($address)
	{
		$this->mailBCC[] = $address;
	}

	function addAttachmentFile($file, $name=false)
	{
		if (file_exists($file))
		{
			$name = (!$name) ? str_replace(dirname($file)."/","",$file) : "att";
			$this->addAttachment(file_get_contents($file), \Tools::getMimetype($file), $name);
			return true;
		}
		else
			return false;
	}

	function addAttachmentContent($content, $name="att", $mimetype="application/octet-stream")
	{
		$this->addAttachment($content, $mimetype, $name);
		return true;
	}

	private function addAttachment($body,$contenttype,$name)
	{
		$attachementBody = "Content-Type: ".$contenttype."\r\n";
		$attachementBody .= "Content-Transfer-Encoding: base64\r\n";
		$attachementBody .= "Content-Disposition: attachment; filename=\"".$name."\"\r\n";
		$attachementBody .= "\r\n".chunk_split(base64_encode($body))."\r\n";
		$this->attachments[] = $attachementBody;
	}

	/**
	 * Zet body van de mail met de standaard mail template
	 * @param string $body	bericht HTML
	 * @param string $css	CSS indien aanwezig
	 */
	function setBody($body,$css=false)
	{
		$tpl = \SmartyTools::getSmarty();
		$tpl->assign("body",$body);
		if ($css)
			$tpl->assign("css",$css);
		$this->bodyHtml = $tpl->fetch("mail");
	}

	function send()
	{
		if (strlen(trim($this->bodyTxt)) == 0)
			$this->bodyTxt = $this->bodyHtml;

		// Create a boundary string. It must be unique so we use the MD5 algorithm to generate a random hash
		$random_hash = md5(date('r', time()));

		// Als debug, naar debug adres versturen!!
		if (\AppRoot::doDebug())
		{
			$this->mailTo = array();
			$this->mailCC = array();
			$this->mailBCC = array();
		}

		// Headers defineren
		$headers = array();
		$headers[] = "To: ".implode(",",$this->mailTo);
		$headers[] = "From: ".$this->fromName." <".$this->fromAddress.">";
		$headers[] = "Reply-To: ".$this->fromAddress;
		$headers[] = "Subject: ".$this->subject;
		$headers[] = "Content-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"";
		$headers[] = "Content-Transfer-Encoding: quoted-printable";
		$headers[] = "Content-Disposition: inline";
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "X-Mailer: PHP/".PHP_VERSION;

		if (count($this->mailCC) > 0)
			$headers[] = "Cc: ".implode(",",$this->mailCC);

		if (count($this->mailBCC))
			$headers[] = "Bcc: ".implode(",",$this->mailBCC);

		// Define the body of the message.
		$body = "--PHP-mixed-".$random_hash."\r\n";
		$body .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		$body .= "Content-Disposition: inline\r\n";
		$body .= "\r\n";
		$body .= "".$this->bodyHtml."\r\n";
		$body .= "\r\n";

		foreach ($this->attachments as $attachment)
		{
			$body .= "--PHP-mixed-".$random_hash."\r\n";
			$body .= $attachment;
		}

		// Voorbereiden om te versturen
		$smtp = new smtp_class();
		$smtp->host_name = "localhost";
		$smtp->host_port = 25;
		$smtp->ssl = 0;

		$addressess = array();
		foreach ($this->mailTo as $mail) {
			$addressess[] = $mail;
		}
		foreach ($this->mailCC as $mail) {
			$addressess[] = $mail;
		}
		foreach ($this->mailBCC as $mail) {
			$addressess[] = $mail;
		}


		// Sturen!
		if ($smtp->host_name == "localhost")
		{
			$header = implode("\r\n",$headers);
			$to = implode(",",array_merge($this->mailTo,$this->mailCC));

			if (mail($to, $this->subject, $body, $header))
				return true;
			else
				return false;
		}
		else
		{
			if ($smtp->SendMessage($this->fromAddress, $addressess, $headers, $body))
				return true;
			else
			{
				\AppRoot::error(print_r($smtp->error,true));
				return false;
			}
		}
	}
}
?>