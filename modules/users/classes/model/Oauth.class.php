<?php
namespace users\model
{
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
		
		/**
		 * after requesting a new Authorization a code is returned. This
		 * code can be used once to get an accessToken.
		 */
		public function getAccessToken($state, $code) {
			// make sure we created the $state by chcking the cache for it.
			$cacheFileName = "sso/" . $state;
			if (\Cache::file()->get($cacheFileName) == null) {
				\AppRoot::error("Reveiced state was not regonized.");
				return null;
			} 
			
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
				$this->state = $state;
				$this->processTokenResponse($curl, $curlresponse);
				$this->verify();
			}
			curl_close($curl);
		}
		
		public function getCrest($character, $url) {
		
		}
		
		// With verify we can request which character it used to login. 
		public function verify() {
			\AppRoot::debug("verifing...");
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
				\AppRoot::error( 'Curl error: ' . curl_error($curl));
			} else {
				$verifydata = json_decode($response);
				if (isset($verifydata->CharacterID)) {
					\AppRoot::debug("verify : Character was = " . $verifydata->CharacterID . " - " . $verifydata->CharacterName);
					// if we have a vippy account logged in...
					$user = User::getUserByToon($verifydata->CharacterID);
					$loggedinUserId = \User::getLoggedInUserId(); 
					if ($loggedinUserId == 0) {
						\AppRoot::debug('Verify: using the verify character for login.');
						$this->doLogin($verifydata);
					} else {
						\AppRoot::debug('Verify : User ' . $loggedinUserId . ' is logged in. Using character for adding');
						// yes, then this might be a character to add.
						$this->addCharacter($loggedinUserId, $verifydata);
					}
				}
			}
			curl_close($curl);
		}
		
		private function doLogin($verifydata) {
			// find the user which belongs to the character.
			$user = User::getUserByToon($verifydata->CharacterID);
			if ($user == null || $user->id == 0) {
				//unknown character and without a vippy account
				// error
				return null;
			} else {
				// stil need to update access and refresh tokens to character.
				$user->setLoginStatus(true);
				\AppRoot::debug("user " . $user->username . " logged in successful. Now update the character ...");
				$this->createOrUpdateCharacter($user->id, $verifydata);
			}
		}
		
		private function addCharacter($loggedinUserId, $verifydata) {
			$user = User::getUserByToon($verifydata->CharacterID);
			$knownCharacter = new \eve\model\Character($verifydata->CharacterID);
			
			// if we know the character, lets check if the owner hasn't changed.
			if ($knownCharacter->crest_ownerhash != null &&$knownCharacter->crest_ownerhash != $verifydata->CharacterOwnerHash) {
				// Owner changed!
				\AppRoot::debug('The known ownerhash is differnt then the verifydata hash. Character has a different owner!'); 
				return;
			} 
			if ($user == null || $user->id == 0) {
				\AppRoot::debug('verify : need to add character to the logged in user');
				$this->createOrUpdateCharacter($loggedinUserId, $verifydata);
				// okay now go back to character overview.
				\AppRoot::redirect("?module=profile&section=chars&addedtoon=".$verifydata->CharacterID, true);
			} else {
				// logged in user must be same as character user.
				if($user->id != $loggedinUserId){
					\AppRoot::debug('fishy. the user logged in '. $loggedinUserId . ' is not the same as ' . $user->id);
					// fishy. the user logged in is not the same as the user from
					// the character.
					return;
				} else {
					// weird, we know the character and the user... still
					// we need to save the access and refresh token.
					$this->createOrUpdateCharacter($loggedinUserId, $verifydata);
				}
			}
		}
		
		
		private function createOrUpdateCharacter($loggedInUserID, $verifyData) {
			
			\AppRoot::debug($verifyData);
			$character = new \eve\model\Character($verifyData->CharacterID);
			\AppRoot::debug("createOrUpdateCharacter loaded above character... resulting in below data");
			\AppRoot::debug($character);
			
			$character->id = $verifyData->CharacterID;
			$character->name = $verifyData->CharacterName;
			$character->userID = $loggedInUserID;
			$character->crest_state = $this->state;
			$character->crest_accesstoken = $this->accesstoken;
			$character->crest_refreshtoken = $this->refreshtoken;
			$character->crest_scopes = $verifyData->Scopes;
			$character->crest_ownerhash = $verifyData->CharacterOwnerHash;
			$character->store();
// 			\AppRoot::redirect("?module=profile&section=chars", true);
		}
		
		private function processTokenResponse($curl, $curlresponse) {
			if ($curlresponse == false) {
				// error
			} else {
				list($headers, $body) = explode("\r\n\r\n", $curlresponse, 2);
				$data = json_decode($body);
				//success
				if (isset($data->access_token)) {
					$this->accesstoken = $data->access_token;
					$this->refreshtoken = $data->refresh_token;
					// todo expire
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

		// Generate a strong random string and save it in the cache
		// we don't know yet who it is.
		private function generateAndSaveUniqueState() {
			$bytes = openssl_random_pseudo_bytes(16, $cstrong);
			$state = bin2hex($bytes);
			$cacheFileName = "sso/" . $state;
			\Cache::file()->set($cacheFileName, '{}');
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