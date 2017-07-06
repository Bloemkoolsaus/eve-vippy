<?php
namespace admin\console;

class Payments
{
    function doImport($arguments=[])
    {
        $limit = 2000;

        $walletApiKey = \admin\model\Payment::getWalletApiKey();
        $walletCharID = \admin\model\Payment::getWalletCharacterID();

        $api = new \eve\controller\API();
        $api->setKeyID($walletApiKey["keyid"]);
        $api->setvCode($walletApiKey["vcode"]);
        $api->setCharacterID($walletCharID);

        $api->setParam("rowCount", $limit);
        if (count($arguments) > 0)
            $api->setParam("fromID", array_shift($arguments));

        $result = $api->call("/char/WalletJournal.xml.aspx");

        if ($errors = $api->getErrors())
        {
            // Foutje bedankt!
            \AppRoot::doCliOutput("Something went wrong", "red");
            return "OOPS";
        }
        else
        {
            \AppRoot::doCliOutput(count($result->result->rowset->row)." transactions found");
            $lowestDate = null;
            $lowestRefID = null;
            $i = 0;
            foreach ($result->result->rowset->row as $row)
            {
                if ($lowestDate == null || $lowestDate > strtotime((string)$row["date"])) {
                    $lowestRefID = (string)$row["refID"];
                    $lowestDate = strtotime((string)$row["date"]);
                }

                if ((int)$row["refTypeID"] == 10 || (int)$row["refTypeID"] == 37)
                {
                    $transaction = new \admin\model\Payment();
                    $transaction->amount = ((string)$row["amount"])*100;
                    $transaction->description = str_replace("DESC:","",(string)$row["reason"]);
                    $transaction->description = trim(str_replace("\n","",$transaction->description));
                    $transaction->date = date("Y-m-d H:i:s", strtotime((string)$row["date"]));

                    if ((int)$row["refTypeID"] == 10) {
                        $transaction->toCharacterID = (string)$row["ownerID2"];
                        $transaction->fromCharacterID = (string)$row["ownerID1"];
                    } else {
                        if ((string)$row["ownerID1"] == $walletCharID) {
                            $transaction->fromCharacterID = (string)$row["ownerID1"];
                            $transaction->toCharacterID = (string)$row["argID1"];
                            $transaction->description = (string)$row["ownerName2"]." - ".$transaction->description;
                        } else {
                            $transaction->fromCharacterID = (string)$row["argID1"];
                            $transaction->toCharacterID = (string)$row["ownerID2"];
                            $transaction->description = (string)$row["ownerName1"]." - ".$transaction->description;
                        }
                    }

                    if ($transaction->amount < 0) {
                        // Negatief. Draai from/to characters om
                        $from = $transaction->fromCharacterID;
                        $to = $transaction->toCharacterID;
                        $transaction->fromCharacterID = $to;
                        $transaction->toCharacterID = $from;
                    }

                    // Komt het van een corp wallet?
                    if ((string)$row["transactionFor"] == "corporate") {
                        $transaction->fromCorporationID = $transaction->fromCharacterID;
                        $transaction->fromCharacterID = null;
                    }

                    \AppRoot::doCliOutput("[".$transaction->date."] ".(($transaction->getFromCorporation())?$transaction->getFromCorporation()->name:"unknown").": ".$transaction->amount." - ".$transaction->description);

                    // Check of vippy in de omschrijving voor komt.
                    if (strpos(strtolower($transaction->description), "vippy") !== false)
                        $transaction->approved = true;

                    if (!$transaction->exists()) {
                        $authgroup = $transaction->findAuthgroup();
                        if ($authgroup !== null) {
                            $transaction->authgroupID = $authgroup->id;
                            \AppRoot::doCliOutput(" + Stored");
                        } else
                            \AppRoot::doCliOutput(" ! No authgroup");

                        $transaction->store();
                    }
                    else
                        \AppRoot::doCliOutput(" - Already registered!");
                }

                $i++;
            }

            \AppRoot::debug("Total records: ".$i);
            if ($i >= 100)
                $this->doImport([$lowestRefID]);
        }

        return "klaar";
    }
}