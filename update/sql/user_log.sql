update  user_log l
    left join eve_db_crius.mapsolarsystems s ON s.solarsystemid = l.whatid
set     l.what = 'delete-wormhole',
        l.extrainfo = CONCAT('{"delete-all":false,"system":{"id":"',s.solarsystemid,'","name":"',s.solarsystemname,'"}}') 
where l.what = 'remove wormhole';

update  user_log l
    left join mapwormholechains c ON c.id = l.whatid
set     l.what = 'delete-wormhole',
        l.extrainfo = CONCAT('{"delete-all":true,"chain":{"id":"',c.id,'","name":"',c.name,'"}}') 
where l.what = 'Mass Delete';