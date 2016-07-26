<?php
namespace users\api;

class Alts extends \api\Server
{
    function getDefault($arguments=[])
    {
        $users = [];
        if (count($arguments) > 0) {
            $character = new \eve\model\Character(array_shift($arguments));
            if ($character && $character->id > 0) {
                $user = $character->getUser();
                if ($user) {
                    foreach ($user->getAuthGroups() as $group) {
                        if ($group->id == $this->getAuthGroup()->id)
                            $users[] = $user;
                    }
                }
            }
        } else
            $users = $this->getAuthGroup()->getAllowedUsers();


        $data = [];
        foreach ($users as $user)
        {
            $userData = [
                "name" => $user->getFullName(),
                "maincharacter" => $user->getMainCharacter()->id,
                "characters" => []
            ];
            foreach ($user->getCharacters() as $character) {
                $characterData = [
                    "id" => $character->id,
                    "name" => $character->name,
                    "corporation" => [
                        "id" => $character->getCorporation()->id,
                        "name" => $character->getCorporation()->name,
                    ]
                ];
                if ($character->getCorporation()->getAlliance()) {
                    $characterData["alliance"] = [
                        "id" => $character->getCorporation()->getAlliance()->id,
                        "name" => $character->getCorporation()->getAlliance()->name
                    ];
                }
                $userData["characters"][] = $characterData;
            }
            $data[] = $userData;
        }

        return $data;
    }
}