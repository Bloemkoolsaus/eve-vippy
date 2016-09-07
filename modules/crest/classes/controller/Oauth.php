<?php
namespace crest\controller;

class Oauth
{
    public $state;
    public $accesstoken;
    public $refreshtoken;
    public $expires;
    public $characterId;
    public $characterOwnerHash;

    public function requestAuthorization()
    {
        $params = [
            'response_type' => 'code',
            'redirect_uri' => \Config::getCONFIG()->get("crest_callback_url"),
            'client_id' => \Config::getCONFIG()->get("crest_clientid"),
            'scope' => $this->getScopes(),
            'state' => $this->generateAndSaveUniqueState()
        ];

        $url = \Config::getCONFIG()->get("crest_login_url")."authorize?".http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        header('Location:'.$url);
        exit();
    }

    /**
     * After requesting a new Authorization a code is returned.
     * This code can be used once to get an accessToken.
     * @param $state
     * @param $code
     * @return null
     */
    public function getAccessToken($state, $code)
    {
        // Make sure we created the $state by checking the cache for it.
        $cacheFileName = "sso/" . $state;
        if (\Cache::file()->get($cacheFileName) == null) {
            $this->error("Reveiced state was not regonized.");
            return null;
        }

        $url = \Config::getCONFIG()->get("crest_login_url").'token';
        $postbody = [
            'grant_type' => 'authorization_code',
            'code' =>  $code
        ];
        $header = [
            "Authorization: Basic " . $this->getBasicAuthorizationCode(),
            "Content-Type: application/json"
        ];
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
            $this->error(curl_error($curl));
        } else {
            $this->state = $state;
            $this->processTokenResponse($curl, $curlresponse);
            $this->verify();
        }
        curl_close($curl);
    }

    /**
     * With verify we can request which character it used to login
     */
    public function verify()
    {
        \AppRoot::doCliOutput("CREST Verifing...");
        $url = \Config::getCONFIG()->get("crest_login_url").'verify';
        $header = [
            'Authorization: Bearer '.$this->accesstoken,
            'Content-Type: application/json',
            'Accept: '.\Config::getCONFIG()->get("crest_accept_version")
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        if ($response == false)
        {
            $this->error(curl_error($curl));
        }
        else
        {
            $verifydata = json_decode($response);
            if (isset($verifydata->CharacterID))
            {
                \AppRoot::doCliOutput("CREST Verify : Character was = " . $verifydata->CharacterID . " - " . $verifydata->CharacterName);

                $url = "profile/characters";
                $user = \User::getUSER();
                if (!$user->loggedIn()) {
                    // No vippy session, login user.
                    \AppRoot::doCliOutput("No vippy session detected. Login user.");
                    $url = "map";
                    $user = $this->doLogin($verifydata);
                }

                if ($user)
                    $this->addCharacter($user, $verifydata);
                else
                    $url = "/";

                \AppRoot::redirect($url);
            }
        }
        curl_close($curl);
    }

    /**
     * Login by CREST
     * @param $data
     * @return \users\model\User|null
     */
    private function doLogin($data)
    {
        // find the user which belongs to the character.
        $character = new \eve\model\Character($data->CharacterID);
        if ($character->getUser())
            $character->getUser()->setLoginStatus(true);

        return $character->getUser();
    }

    private function addCharacter(\users\model\User $user, $data)
    {
        $character = new \crest\model\Character($data->CharacterID);
        $token = $character->getToken();
        if (!$token) {
            $token = new \crest\model\Token();
        }

        // Detect owner change
        if ($token->ownerHash != null && $token->ownerHash != $data->CharacterOwnerHash)
        {
            // Owner changed!
            \AppRoot::doCliOutput('The known ownerhash is differnt then the verifydata hash. Character has a different owner!');
            return;
        }

        // Check user
        if ($character->getUser()) {
            \AppRoot::doCliOutput("Character user: ".$character->getUser()->displayname);
            \AppRoot::doCliOutput("Selected user: ".$user->displayname);
            if ($character->getUser()->id != $user->id) {
                \AppRoot::doCliOutput("USER MISMATCH!");
            }
        }

        $character->id = $data->CharacterID;
        $character->name = $data->CharacterName;
        $character->userID = $user->id;
        $character->store();
        $character->importData();

        $token->tokenID = $character->id;
        $token->tokenType = strtolower($data->TokenType);
        $token->ownerHash = $data->CharacterOwnerHash;
        $token->state = $this->state;
        $token->accessToken = $this->accesstoken;
        $token->refreshToken = $this->refreshtoken;
        $token->scopes = json_encode(explode(" ",$data->Scopes));

        $expireDate = new \DateTime($data->ExpiresOn);
        $token->expires = $expireDate->format("Y-m-d H:i:s");

        $token->store();
    }


    private function processTokenResponse($curl, $curlresponse)
    {
        if ($curlresponse == false) {
            $this->error(curl_error($curl));
        } else {
            list($headers, $body) = explode("\r\n\r\n", $curlresponse, 2);
            $data = json_decode($body);
            //success
            if (isset($data->access_token)) {
                $this->accesstoken = $data->access_token;
                $this->refreshtoken = $data->refresh_token;
                // todo expire
            } else if (isset($data->error_description)) {
                $this->error($data->error_description);
            }
        }
    }

    private function getBasicAuthorizationCode()
    {
        $concat = \Config::getCONFIG()->get("crest_clientid").":".\Config::getCONFIG()->get("crest_secret_key");
        $base = base64_encode($concat);
        return $base;
    }

    // Generate a strong random string and save it in the cache
    // we don't know yet who it is.
    private function generateAndSaveUniqueState()
    {
        $bytes = openssl_random_pseudo_bytes(16, $cstrong);
        $state = bin2hex($bytes);
        $cacheFileName = "sso/" . $state;
        \Cache::file()->set($cacheFileName, '{}');
        return $state;
    }

    private function getScopes()
    {
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
                        'characterLocationRead', 	//: Allows an application to read your characters real time location in EVE.
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
                        'fleetRead', 		//: Allows real time reading of your fleet information (members, ship types, etc.) if you're the boss of the fleet.
        // 				'fleetWrite', 		//: Allows the ability to invite, kick, and update fleet information if you're the boss of the fleet.
                        'publicData', 		//: Allows access to public data.
        // 				'remoteClientUI',   //Allows applications to control the UI of your EVE Online client
        // 				'structureVulnUpdate' 		//: Allows updating your structures' vulnerability timers.
        );
        // must be string delimited with spaces;
        return implode(' ', $scopes);
    }

    function error($error)
    {
        \AppRoot::error("CREST SSO Error: ".$error, false);

    }
}