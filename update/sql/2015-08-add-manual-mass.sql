alter table mapwormholejumplog add column mass int(11) after shipid;
update mapwormholejumplog j
    inner join eve_db_aegis.invtypes i on i.typeid = j.shipid
set j.mass = i.mass;