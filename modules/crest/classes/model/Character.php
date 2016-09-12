<?php
namespace crest\model;

class Character extends \eve\model\Character
{
    protected $_token = null;


    function importData()
    {
        /**
         * Gebruikt reguliere xml api
         *      scheelt weer crest-requests. Limits enzo
         */
        $charController = new \eve\controller\Character();
        $character = $charController->importCharacter($this->id);

        // Check corp
        $corpController = new \eve\controller\Corporation();
        $corpController->importCorporation($character->corporationID);
    }

    /**
     * Get CREST Token
     * @return \crest\model\Token|null
     */
    function getToken()
    {
        if ($this->_token === null)
            $this->_token = \crest\model\Token::findOne(["tokentype" => "character", "tokenid" => $this->id]);

        return $this->_token;
    }
}