<?php
namespace admin\console;

class Payments
{
    function doImport($arguments=[])
    {
        $limit = 2000;

        $api = new \eve\controller\API();
        $api->setKeyID(\AppRoot::getDBConfig("wallet_api_keyid"));
        $api->setvCode(\AppRoot::getDBConfig("wallet_api_vcode"));
        $api->setCharacterID(\AppRoot::getDBConfig("wallet_api_charid"));

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
                    $transaction = new \admin\model\SubscriptionTransaction();
                    $transaction->amount = ((string)$row["amount"])*100;
                    $transaction->description = str_replace("DESC:","",(string)$row["reason"]);
                    $transaction->description = trim(str_replace("\n","",$transaction->description));
                    $transaction->date = date("Y-m-d H:i:s", strtotime((string)$row["date"]));

                    \AppRoot::doCliOutput("(".$transaction->date.") ".$transaction->amount." - ".$transaction->description);

                    // Check of vippy in de omschrijving voor komt.
                    $registerTransaction = false;
                    if (strpos(strtolower($transaction->description), "vippy") !== false)
                        $registerTransaction = true;
                    if (strpos(strtolower($transaction->description), "atlas") !== false)
                        $registerTransaction = true;

                    if (!$registerTransaction)
                    {
                        \AppRoot::doCliOutput(" - No vippy/atlas in description");
                        continue;
                    }

                    if ((int)$row["refTypeID"] == 10) {
                        $transaction->toCharacterID = (string)$row["ownerID2"];
                        $transaction->fromCharacterID = (string)$row["ownerID1"];
                    } else {
                        if ((string)$row["ownerID1"] == \AppRoot::getDBConfig("wallet_api_charid")) {
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

                    if (!$transaction->exists()) {
                        $authgroup = $transaction->findAuthgroup();
                        if ($authgroup !== null) {
                            $transaction->authgroupID = $authgroup->id;
                            $transaction->store();
                            \AppRoot::doCliOutput(" + Stored");
                        } else
                            \AppRoot::doCliOutput(" - No authgroup");
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