update users_notification set readdate = now();

insert into users_notification (userid, type, title, content, notifydate, readdate)
select  id, 'message', '',
'<div style=\"text-align: left; padding: 2em\">
<h2>Vippy updated to support CREST</h2>
<b>Panick is over. Vippy has been updated to support crest.</b>
Tracking has changed A LOT. See these <a href=\'/help/crest\' target=\'_blank\'>help pages</a> on how it all works.
See the <a href=\'/index.php?module=admin&section=changelog\' target=\'_blank\'>patchnotes</a> for a full list of changes.

But Bloem, there has been so much time to prepare?
Yes.. Unfortunately, documentation on CREST is incomplete so I had to figure out stuff out the hard way. Then there is lot\'s of limitations to CREST, especially for tracking. Rate limits being the most annoying.
This meant I spent A LOT of time figuring out how to best handle it. In case you are wondering, Tripwire / Pathfinder / etc don\'t have this problem because they are distributed (you host it yourself).

There are still bugs in this Vippy. Some of wich i know, others i may not know yet. If you find bugs (or worse, errors), please let me know!!
BUT: <b>please don\'t start mailing me at random</b>. Please collect bug reports in your corp/alliance and send them to me in a batches. (otherwise i might go crazy).
</div>', now(), null
from    users
where   isvalid > 0;