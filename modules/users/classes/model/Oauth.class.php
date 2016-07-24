<?php
namespace users\model
{
	/*
	 * login by eve
	 * 1 - create new random state and save it in the db. 
	 * 2 - request authorization by eve.
	 * 3 - catch callback, find the state in the database. 
	 * 4 - request tokens.
	 * 
	 */

	class Oauth
	{
		public $state;
		public $accesstoken;
		public $refreshtoken;
		public $expires;
		public $characterId; 
		public $characterOwnerHash;
		

		public function requestAuthorization(){
			$params = array('response_type' => 'code',
					'redirect_uri' => CREST_CALLBACK_URL,
					'client_id' => CREST_CLIENT_ID,
					'scope' => $this->getScopes(),
					'state' => $this->generateAndSaveUniqueState()
			);
			
			$url = CREST_LOGIN_URL . "/authorize/?" . http_build_query($params , '', '&', PHP_QUERY_RFC3986);
			header('Location:'.$url);
			exit();
		}
		
		/*
		 * after requesting a new Authorization a code is returned. This
		 * code can be used once to get an accessToken.
		 */
		public function getAccessToken($state, $code) {
			$url = CREST_LOGIN_URL . '/token';		
			$postbody = array('grant_type' => 'authorization_code',
					'code' =>  $code
				);
			$header = array("Authorization: Basic " . $this->getBasicAuthorizationCode(),
					"Content-Type: application/json" 
				);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postbody));
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$curlresponse = curl_exec($curl);
		
			if ($curlresponse == false) {
				echo 'Curl error: ' . curl_error($curl);
			} else {
				$this->processTokenResponse($curl, $curlresponse, $state);
				$this->verify();
			}
			curl_close($curl);
		}
		
		public function load($state=false) {
			if (!$state) {
			   return;
			}
			$resultset = \MySQL::getDB()->getRow("SELECT * FROM users_oauth WHERE state = ?", array($state));
			if ($resultset)
			{
				$this->state = $resultset["state"];
				$this->accesstoken = $resultset["accesstoken"];
				$this->refreshtoken = $resultset["refreshtoken"];
				$this->characterId = $resultset["CharacterId"];
				$this->characterOwnerHash = $resultset["CharacterOwnerHash"];
			}
		}
		
		public function store() {
			if ($this->state == null)
				return false;
				
			$oauth = array();
			$oauth["state"] = $this->state;
			$oauth["accesstoken"] = $this->accesstoken;
			$oauth["refreshtoken"] = $this->refreshtoken;
			$oauth["CharacterID"] = $this->characterId;
			$oauth["CharacterOwnerHash"] = $this->characterOwnerHash;
				
			$result = \MySQL::getDB()->updateinsert("users_oauth", $oauth, array("state" => $this->state));
				
		}
		
		// With verify we can request which character it used to login. 
		public function verify() {
			$url = CREST_LOGIN_URL . '/verify';
			$header = array('Authorization: Bearer ' . $this->accesstoken,
					'Content-Type: application/json');
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($curl);
			if ($response == false) {
				echo 'Curl error: ' . curl_error($curl);
			} else {
				$data = json_decode($response);
				if (isset($data->CharacterID)) {
					// if we have a characterID we have a valid eve login.
					// Now we need to find a vippy account for it.  
					$user = User::getUserByToon($data->CharacterID);
					if ($user == null) {
						// don't have a vippy account related to this character. 
					} else {
						$user->setLoginStatus(true);
						$this->characterId = $data->CharacterID;
						$this->characterOwnerHash = $data->CharacterOwnerHash;
						$this->store();
					}
				}
				// {"CharacterID":92457020,"CharacterName":"Xion Sharvas","ExpiresOn":"2016-07-24T18:58:21","Scopes":"characterLocationRead","TokenType":"Character","CharacterOwnerHash":"gV8Mhoj3ax7Q1OgRc16153kGSWY=","IntellectualProperty":"EVE"}
				
			}
			curl_close($curl);
		}
		
		
		
		private function processTokenResponse($curl, $curlresponse, $state) {
			if ($curlresponse == false) {
				// error
			} else {
				$this->load($state);
				list($headers, $body) = explode("\r\n\r\n", $curlresponse, 2);
				$data = json_decode($body);
				//success
				if (isset($data->access_token)) {
					$this->accesstoken = $data->access_token;
					$this->refreshtoken = $data->refresh_token;
					$this->store();
				} else if (isset($data->error_description) && $data->error_description == 'Authorization code not found') {
					print("error -> request new");
				}
			}
		}
		
		
		
		private function getBasicAuthorizationCode() {
			$concat = CREST_CLIENT_ID . ":" . CREST_SECRET_KEY;
			$base = base64_encode($concat);
			return $base;
		}

		// Generate a strong random string and save it.
		private function generateAndSaveUniqueState() {
			$bytes = openssl_random_pseudo_bytes(16, $cstrong);
			$state = bin2hex($bytes);
			$oauth["state"] = $state;
			$result = \MySQL::getDB()->insert("users_oauth", $oauth);
			return $state;
		}
		
		private function getScopes() {
			// All possible scopes, for now just the location
			$scopes = array(
			// 				'characterAccountRead',   // Read your account subscription status.
			// 				'characterAssetsRead',    // Read your asset list.
			// 				'characterBookmarksRead', // List your bookmarks and their coordinates.
			// 				'characterCalendarRead',  // Read your calendar events and attendees.
			// 				'characterChatChannelsRead',  //: List chat channels you own or operate.
			// 				'characterClonesRead', 		//: List your jump clones, implants, attributes, and jump fatigue timer.
			// 				'characterContactsRead',	//: Allows access to reading your characters contacts.
			// 				'characterContactsWrite', 	// Allows applications to add, modify, and delete contacts for your character.
			// 				'characterContractsRead',	//: Read your contracts.
			// 				'characterFactionalWarfareRead', //: Read your factional warfare statistics.
			// 				'characterFittingsRead', 	//: Allows an application to view all of your character's saved fits.
			// 				'characterFittingsWrite',	// Allows an application to create and delete the saved fits for your character.
			// 				'characterIndustryJobsRead', //: List your industry jobs.
			// 				'characterKillsRead', 		//: Read your kill mails.
			'characterLocationRead' 	//: Allows an application to read your characters real time location in EVE.
			// 				'characterLoyaltyPointsRead', //: List loyalty points your character has for the different corporations.
			// 				'characterMailRead', 		//: Read your EVE Mail.
			// 				'characterMarketOrdersRead', //: Read your market orders.
			// 				'characterMedalsRead', 		//: List your public and private medals.
			// 				'characterNavigationWrite', //: Allows an application to set your ships autopilot destination.
			// 				'characterNotificationsRead',	//: Receive in-game notifications.
			// 				'characterOpportunitiesRead', //: List the opportunities your character has completed.
			// 				'characterResearchRead', 		//: List your research agents working for you and research progress.
			// 				'characterSkillsRead', 		//: Read your skills and skill queue.
			// 				'characterStatsRead', 		//: Yearly aggregated stats about your character.
			// 				'characterWalletRead', 		//: Read your wallet status, transaction, and journal history.
			// 				'corporationAssetRead', 		//: Read your corporation's asset list.
			// 				'corporationBookmarksRead', 		//: List your corporation's bookmarks and their coordinates.
			// 				'corporationContractsRead', 		//: List your corporation's contracts.
			// 				'corporationFactionalWarfareRead', 		//: Read your corporation's factional warfare statistics.
			// 				'corporationIndustryJobsRead', 		//: List your corporation's industry jobs.
			// 				'corporationKillsRead', 		//: Read your corporation's kill mails.
			// 				'corporationMarketOrdersRead', 		//: List your corporation's market orders.
			// 				'corporationMedalsRead', 		//: List your corporation's issued medals.
			// 				'corporationMembersRead', 		//: List your corporation's members, their titles, and roles.
			// 				'corporationShareholdersRead', 		//: List your corporation's shareholders and their shares.
			// 				'corporationStructuresRead', 		//: List your corporation's structures, outposts, and starbases.
			// 				'corporationWalletRead', 		//: Read your corporation's wallet status, transaction, and journal history.
			// 				'fleetRead', 		//: Allows real time reading of your fleet information (members, ship types, etc.) if you're the boss of the fleet.
			// 				'fleetWrite', 		//: Allows the ability to invite, kick, and update fleet information if you're the boss of the fleet.
			// 				'publicData', 		//: Allows access to public data.
			// 				'remoteClientUI',   //Allows applications to control the UI of your EVE Online client
			// 				'structureVulnUpdate' 		//: Allows updating your structures' vulnerability timers.
			);
			// must be string delimited with spaces;
			return implode(' ', $scopes);
		}
	}
	
	
}
?>