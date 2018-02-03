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
        \AppRoot::redirect($url, false);
    }

    /**
     * Succesvolle SSO Login. Haal de ingelogde toon.
     * @param $state
     * @param $code
     */
    function verify($state, $code)
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
                                $token = new \sso\model\Token();

                            // Token opslaan
                            \AppRoot::doCliOutput("Store token: ".$accessToken);
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
                            if ($token->ownerHash && $token->ownerHash != $result->CharacterOwnerHash) {
                                \AppRoot::doCliOutput('The known ownerhash is different then the verifydata hash. Character has a different owner!');
                            }

                            // Check voor een login sessie. Login als er nog geen sessie is.
                            if (!\User::getUSER()) {
                                if ($character->getUser())
                                    $character->getUser()->setLoginStatus(true, $stateData->remember);
                            }

                            if (\User::getUSER()) {
                                // Check user
                                if ($character->getUser()) {
                                    \AppRoot::doCliOutput("Character user: " . $character->getUser()->displayname);
                                    \AppRoot::doCliOutput("Selected user: " . \User::getUSER()->displayname);
                                    if ($character->getUser()->id != \User::getUSER()->id)
                                        \AppRoot::doCliOutput("USER MISMATCH!");
                                }

                                // Update character data
                                $character->userID = \User::getUSER()->id;
                                $character->store();
                                $character->importData();

                                // Redirect naar state
                                \AppRoot::redirect($stateData->url, false);
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
        $scopes = [
            "esi-location.read_location.v1",
            "esi-location.read_ship_type.v1",
            "esi-fleets.read_fleet.v1",
            "esi-fleets.write_fleet.v1",
            "esi-ui.write_waypoint.v1",
            "esi-characters.read_corporation_roles.v1",
            "esi-location.read_online.v1"
        ];
        return $scopes;
    }
}