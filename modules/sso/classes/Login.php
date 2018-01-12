<?php
namespace sso;

class Login extends \api\Client
{
    function __construct($baseURL=false)
    {
        if (!$baseURL)
            $baseURL = \Config::getCONFIG()->get("sso_login_url");

        parent::__construct($baseURL);
        $this->setContentType("application/json");
    }

    function get($url, $params=[])
    {
        return parent::get($url, $params);
    }

    function post($url, $params=[])
    {
        return parent::post($url, $params);
    }

    /**
     * Request sso-login
     * @param $redirectURL
     * @param bool $remember
     */
    public function loginSSO($redirectURL, $remember=false)
    {
        \AppRoot::doCliOutput("[SSO] Login->loginSSO($redirectURL)");
        $params = [
            'response_type' => 'code',
            'redirect_uri' => \Config::getCONFIG()->get("sso_callback_url"),
            'client_id' => \Config::getCONFIG()->get("sso_clientid"),
            'scope' => implode(' ', $this->getScopes()),
            'state' => $this->generateState($redirectURL, $remember)
        ];

        $url = \Config::getCONFIG()->get("sso_login_url")."authorize?".http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        \AppRoot::redirect($url,false);
    }

    /**
     * Succesvolle SSO Login. Haal een accesstoken.
     * @param $state
     * @param $code
     */
    function getToken($state, $code)
    {
        \AppRoot::doCliOutput("[SSO] Login->getToken($state, $code)");
        $stateData = $this->getState($state);
        \AppRoot::debug("state:<pre>".print_r($stateData,true)."</pre>");
        if ($stateData && isset($stateData->url))
        {
            $this->resetheader();
            $this->addHeader("Authorization: Basic " . $this->getBasicAuthorizationCode());
            $this->post("token", ["grant_type" => "authorization_code", "code" => $code]);
            $this->closeCurl();

            if ($this->success())
            {
                $data = $this->getResult();
                if (isset($data->access_token))
                {
                    \AppRoot::doCliOutput(" * Verification");
                    $accessToken = $data->access_token;
                    $refreshToken = $data->refresh_token;

                    // Verify the token
                    $this->resetheader();
                    $this->addHeader("Authorization: Bearer ".$data->access_token);
                    $this->get("verify");
                    $this->closeCurl();

                    if ($this->success())
                    {
                        $result = $this->getResult();
                        if (isset($result->CharacterID))
                        {
                            \AppRoot::doCliOutput(" * [" . $result->CharacterID . "] " . $result->CharacterName . " verified");

                            // Character importeren
                            $character = \eve\model\Character::findByID($result->CharacterID);
                            if (!$character) {
                                $character = new \eve\model\Character();
                                $character->id = $result->CharacterID;
                                $character->name = $result->CharacterName;
                                $character->store();
                            }
                            $token = $character->getToken();
                            if (!$token)
                                $token = new \crest\model\Token();

                            // Token opslaan
                            $token->tokenid = $character->id;
                            $token->tokentype = strtolower($result->TokenType);
                            $token->ownerHash = $result->CharacterOwnerHash;
                            $token->state = $state;
                            $token->accessToken = $accessToken;
                            $token->refreshToken = $refreshToken;
                            $token->scopes = json_encode(explode(" ", $result->Scopes));
                            $token->expires = (new \DateTime($result->ExpiresOn))->format("Y-m-d H:i:s");
                            $token->store();

                            // Detect owner change
                            if ($token->ownerHash && $token->ownerHash != $result->CharacterOwnerHash)
                                \AppRoot::doCliOutput('The known ownerhash is different then the verifydata hash. Character has a different owner!');

                            // Check voor een login sessie. Login als er nog geen sessie is.
                            if (!\User::getUSER()) {
                                if ($character->getUser())
                                    $character->getUser()->setLoginStatus(true, $state->remember);
                            }

                            if (\User::getUSER()) {
                                // Check user
                                if ($character->getUser()) {
                                    \AppRoot::doCliOutput("Character user: " . $character->getUser()->displayname);
                                    \AppRoot::doCliOutput("Selected user: " . \User::getUSER()->displayname);
                                    if ($character->getUser()->id != \User::getUSER()->id)
                                        \AppRoot::doCliOutput("USER MISMATCH!");
                                }
                                $character->userID = \User::getUSER()->id;
                                $character->store();
                                $character->importData();

                                \AppRoot::redirect($stateData->url);
                            } else {
                                \AppRoot::doCliOutput("No user found");
                                \AppRoot::redirect("users/login/no-account/" . $character->id);
                            }
                        }
                    }
                } else {
                    if (isset($data->error_description))
                        \AppRoot::error($data->error_description);
                }
            } else
                \AppRoot::doCliOutput("Could not collect token");
        } else
            \AppRoot::doCliOutput("State not found!!");

        \AppRoot::redirect("/");
    }

    /**
     * Refresh a token
     * @param model\Token $token
     * @return \sso\model\Token|boolean false on failure
     */
    function refresh(\sso\model\Token $token)
    {
        \AppRoot::doCliOutput("[CREST] Login->refreshToken($token->accessToken)");

        $this->resetheader();
        $this->addHeader("Authorization: Basic " . $this->getBasicAuthorizationCode());
        $this->post("token", ["grant_type" => "refresh_token", "refresh_token" => $token->refreshToken]);

        $result = $this->getResult();
        if ($this->success()) {
            if (isset($result->access_token)) {
                $token->accessToken = $result->access_token;
                $token->refreshToken = $result->refresh_token;
                $token->expires = date("Y-m-d H:i:s", strtotime("now")+$result->expires_in);
                $token->store();
                return $token;
            }
        } else {
            if (isset($result->error)) {
                if (trim(strtolower($result->error)) == "invalid_grant") {
                    // Toegang ontzegd. Delete deze token.
                    \AppRoot::doCliOutput("Delete this token. Access revoked: ".$result->error_description);
                    $token->delete();
                }
            }
        }

        return false;
    }

    /**
     * Get auth key
     * @return string
     */
    private function getBasicAuthorizationCode()
    {
        $concat = \Config::getCONFIG()->get("sso_clientid").":".\Config::getCONFIG()->get("sso_secret_key");
        $base = base64_encode($concat);
        return $base;
    }

    /**
     * Genereer een state zodat we de login kunnen herkennen op de redirect
     * @param string $redirectURL
     * @param bool $remember
     * @return string
     */
    private function generateState($redirectURL, $remember=false)
    {
        $bytes = openssl_random_pseudo_bytes(16, $cstrong);
        $state = bin2hex($bytes);
        \Cache::file()->set("sso/state/".$state, json_encode(["url" => $redirectURL, "remember" => ($remember)?1:0]));
        return $state;
    }

    private function getState($state)
    {
        $cache = \Cache::file()->get("sso/state/".$state);
        if ($cache) {
            $cache = json_decode($cache);
            \Cache::file()->remove("sso/state/".$state);
            return $cache;
        }

        return null;
    }

    /**
     * Get scopes
     * @return array
     */
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
            'characterNavigationWrite', //: Allows an application to set your ships autopilot destination.
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

        return $scopes;
    }
}