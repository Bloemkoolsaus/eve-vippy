<?php
namespace api\client\plugin
{
	/**
	 * WS-Security Signature plugin implementation for SOAP Clients
	 *
	 * Signs requests using an SSL certificate and private key in a PEM or PKCS12 container.
	 *
	 * To normalize the XML for signing, the xmllint utility (in package libxml2-utils) is used.
	 *
	 * This plugin only signs the env:Body part of the request, but is easily adjusted to sign
	 * any other element (see getSignedInfo).
	 */
	class Signature extends \api\client\Plugin implements Raw
	{
		/**
		 * Path to xmllint
		 */
		const XMLLINT_PATH = '/usr/bin/xmllint';

		/**
		 * XML namespaces
		 */
		const ENV_NS_11 = 'http://schemas.xmlsoap.org/soap/envelope/';
		const ENV_NS_12 = 'http://www.w3.org/2003/05/soap-envelope';
		const WSU_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
		const WSSE_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
		const DS_NS = 'http://www.w3.org/2000/09/xmldsig#';

		/**
		 * Certificate/key container types
		 */
		const TYPE_PKCS12 = 'PKCS12';
		const TYPE_PEM = 'PEM';

		/**
		 * @var string
		 */
		protected $filename;

		/**
		 * @var string
		 */
		protected $password;

		/**
		 * @var string
		 */
		protected $type;

		/**
		 * Private key handle
		 * @var resource
		 */
		protected $privateKey;

		/**
		 * Certificate string
		 * @var string
		 */
		protected $certificate;


		/**
		 * Constructor
		 * @param string $filename
		 * @param string $password
		 */
		function __construct($filename, $password = '')
		{
			$this->filename = $filename;
			$this->password = $password;

			if (!file_exists($filename))
				throw new \RuntimeException("Certificate file not found: " . $filename);

			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			if (in_array($extension, array('p12', 'pfx')))
				$this->type = self::TYPE_PKCS12;
			else
				$this->type = self::TYPE_PEM;

			$this->load();
		}

		/**
		 * Destructor
		 */
		function __destruct()
		{
			$this->unload();
		}

		/**
		 * Load key and certificate
		 */
		private function load()
		{
			$contents = file_get_contents($this->filename);
			$certificate = null;
			if ($this->type == self::TYPE_PKCS12)
			{
				openssl_pkcs12_read($contents, $certs, $this->password);

				$this->privateKey = openssl_pkey_get_private($certs['pkey']);
				$this->certificate = $this->normalizeCertificate($certs['cert']);
			}
			elseif ($this->type == self::TYPE_PEM)
			{
				$this->privateKey = openssl_pkey_get_private($contents, $this->password);

				$x509 = openssl_x509_read($contents);
				openssl_x509_export($x509, $certificate);
				openssl_x509_free($x509);

				$this->certificate = $this->normalizeCertificate($certificate);
			}

			if (!$this->privateKey)
				throw new \RuntimeException('Could not load private key');

			if (!$this->certificate)
				throw new \RuntimeException('Could not load certificate');
		}

		/**
		 * Unload key and certificate
		 */
		private function unload()
		{
			openssl_free_key($this->privateKey);

			$this->privateKey  = null;
			$this->certificate = null;
		}

		/**
		 * Wrap a raw request
		 * @param string $request Request to pass on to next closure
		 * @param callable $next Next closure to call
		 * @return string Response received from the closure
		 */
		function raw($request, callable $next)
		{
			$signed = $this->sign($request);

			return $next($signed);
		}

		/**
		 * Sign raw XML request
		 *
		 * @param string $request SOAP request XML
		 * @return string Signed request XML
		 */
		function sign($request)
		{
			// spend the addition of the desired title
			$dom = new \DOMDocument('1.0', 'utf-8');
			$dom->loadXML($request);

			// Detect SOAP envelope XML namespace and prefix
			if ($this->getClient()->getOption("soap_version") == SOAP_1_2)
				$envNS = self::ENV_NS_12;
			else
				$envNS = self::ENV_NS_11;
			$envPrefix = $dom->lookupPrefix($envNS);

			// put a wsu:Id in the request body
			/** @var \DOMElement $bodyNode */
			$bodyNode = $dom->getElementsByTagNameNS($envNS, 'Body')->item(0);
			$bodyNode->setAttributeNS(self::WSU_NS, 'wsu:Id', 'reqBody');

			// find node SoapHeader, and if it is not - create
			$headerNode = $dom->getElementsByTagNameNS($envNS, 'Header')->item(0);
			if (!$headerNode)
				$headerNode = $dom->documentElement->insertBefore($dom->createElementNS($envNS, $envPrefix . ':Header'), $bodyNode);

			// Add items wsse:Security, which will be wrapped in the signature info
			$secNode = $headerNode->appendChild($dom->createElementNS(self::WSSE_NS, 'wsse:Security'));

			// add a reference to the key in the title
			$tokenId = 'Security-Token-' . $this->getUUID($this->certificate);
			$token = $dom->createElementNS(self::WSSE_NS, 'wsse:BinarySecurityToken', $this->certificate);
			$token->setAttribute('ValueType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3');
			$token->setAttribute('EncodingType', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary');
			$token->setAttributeNS(self::WSU_NS, 'wsu:Id', $tokenId);

			// add a link to the key
			$secNode->appendChild($token);

			// link to the signed item
			$signNode = $secNode->appendChild($dom->createElementNS(self::DS_NS, 'ds:Signature'));
			$signInfo = $signNode->appendChild($this->getSignedInfo($dom, array('reqBody')));

			// and the signature itself
			openssl_sign($this->normalizeNode($signInfo), $signature, $this->privateKey, OPENSSL_ALGO_SHA1);

			$signNode->appendChild($dom->createElementNS(self::DS_NS, 'ds:SignatureValue', base64_encode($signature)));
			$keyInfo = $signNode->appendChild($dom->createElementNS(self::DS_NS, 'ds:KeyInfo'));
			$secTokRef = $keyInfo->appendChild($dom->createElementNS(self::WSSE_NS, 'wsse:SecurityTokenReference'));
			/** @var \DOMElement $keyRef */
			$keyRef = $secTokRef->appendChild($dom->createElementNS(self::WSSE_NS, 'wsse:Reference'));
			$keyRef->setAttribute('URI', "#{$tokenId}");

			return $dom->saveXML();
		}

		/**
		 * Get SignedInfo element
		 *
		 * The second parameter is a list of values wsu:Id signed by the elements
		 *
		 * @param \DOMDocument $dom
		 * @param array $ids
		 * @return \DOMNode
		 */
		function getSignedInfo($dom, $ids)
		{
			$xpath = new \DOMXPath($dom);
			$xpath->registerNamespace('wsu', self::WSU_NS);
			$xpath->registerNamespace('ds', self::DS_NS);

			$signedInfo = $dom->createElementNS(self::DS_NS, 'ds:SignedInfo');
			// canonicalization algorithm
			/** @var \DOMElement $method */
			$method = $signedInfo->appendChild($dom->createElementNS(self::DS_NS, 'ds:CanonicalizationMethod'));
			$method->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
			// signature Algorithm
			$method = $signedInfo->appendChild($dom->createElementNS(self::DS_NS, 'ds:SignatureMethod'));
			$method->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');

			foreach ($ids as $id)
			{
				// find a node and its canonical representation
				$nodes = $xpath->query("//*[(@wsu:Id='{$id}')]");
				if ($nodes->length == 0)
					continue;
				$normalized = $this->normalizeNode($nodes->item(0));

				// Reference to create a node
				/** @var \DOMElement $reference */
				$reference = $signedInfo->appendChild($dom->createElementNS(self::DS_NS, 'ds:Reference'));
				$reference->setAttribute('URI', "#{$id}");
				$transforms = $reference->appendChild($dom->createElementNS(self::DS_NS, 'ds:Transforms'));
				/** @var \DOMElement $transform */
				$transform = $transforms->appendChild($dom->createElementNS(self::DS_NS, 'ds:Transform'));
				// indicate what spent kanonizaciû
				$transform->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
				// and do with SHA1 digest
				/** @var \DOMElement $method */
				$method = $reference->appendChild($dom->createElementNS(self::DS_NS, 'ds:DigestMethod'));
				$method->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
				$reference->appendChild($dom->createElementNS(self::DS_NS, 'ds:DigestValue', base64_encode(sha1($normalized, true))));
			}

			return $signedInfo;
		}

		/**
		 * Returns a random UUID , or on the basis of data
		 * @param mixed $data
		 * @return string
		 */
		function getUUID($data = null)
		{
			if ($data === null)
				$data = microtime() . uniqid();

			$id = md5($data);
			return sprintf('%08s-%04s-%04s-%04s-%012s', substr($id, 0, 8), substr($id, 8, 4), substr($id, 12, 4),
						   substr(16, 4), substr($id, 20));
		}

		/**
		 * Returns a string with the canonized record DOMNode
		 * @param \DOMNode $node
		 * @return string
		 */
		private function normalizeNode($node)
		{
			$dom = new \DOMDocument('1.0', 'utf-8');
			$dom->appendChild($dom->importNode($node, true));

			return $this->normalizeXML($dom->saveXML($dom->documentElement));
		}

		/**
		 * Normalize XML
		 *
		 * @param string $xml
		 * @return string
		 */
		private function normalizeXML($xml)
		{
			$command     = self::XMLLINT_PATH . ' --exc-c14n -';
			$descriptors = array(0 => array("pipe", "r"), 1 => array("pipe", "w"));
			$process     = proc_open($command, $descriptors, $pipes);
			if (!is_resource($process))
				throw new \RuntimeException("Could not open process: " . $command);

			fwrite($pipes[0], $xml);
			fclose($pipes[0]);

			$result = stream_get_contents($pipes[1]);
			fclose($pipes[1]);

			$exit = proc_close($process);
			if ($exit)
				throw new \RuntimeException("xmllint returned exit code " . $exit);

			return $result;
		}

		/**
		 * Normalize certificate
		 * @param string $certificate
		 * @return string
		 */
		private function normalizeCertificate($certificate)
		{
			$certificateLines = explode("\n", $certificate);
			array_shift($certificateLines);
			while (!trim(array_pop($certificateLines))) {}
			array_walk($certificateLines, 'trim');

			return implode('', $certificateLines);
		}

	}
}
?>