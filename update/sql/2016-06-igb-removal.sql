update users_notification set readdate = now();

insert into users_notification (userid, type, title, content, notifydate, readdate)
select  id, 'notice', '!! IGB removal announcment !!', '<div style=\"text-align: left;\">By now, most of you have seen CCP''s announcment about removing the ingame browser. (<a href=\"https://community.eveonline.com/news/dev-blogs/bidding-farewell-to-the-in-game-browser\" target=\"_blank\">link</a>)

I have already received A LOT (to many lol) of questions from people that are worried that this will impact VIPPY, wich it will !!!
Location tracking is available via CCP''s new CREST api''s, however, these are not (yet) ideal and do not work as well I would like them to. We will see how these CREST calls develop while the IGB removal comes closer. I will update VIPPY accordingly.

<b>TLDR:</b> <u>After the IGB is removed, VIPPY will still be able to track your location via the CREST API!</u>
In what form and how exactly that is going to work in VIPPY I don''t yet know. There are multiple CREST API calls I could use and CCP might change some of those. We will know more as I experiment with CREST and the deadline approaches.</div>', now(), null
from    users
where   isvalid > 0