create table mapwormholes_shattered (
	constellationid int(11) unsigned not null,
	type varchar(255) default 'normal',
	primary key (constellationid)
);

insert into mapwormholes_shattered (constellationid, type)
select constellationid, 'normal'
from eve_db_rhea.mapconstellations
where constellationname in ('A-C00325','B-C00326','C-C00327','D-C00328','E-C00329','F-C00330');

insert into mapwormholes_shattered (constellationid, type)
select constellationid, 'frigate'
from eve_db_rhea.mapconstellations
where constellationname in ('H-C00331','H-C00332','H-C00333');


update mapwormholetypes set destination = 0 where name = 'A009';
update mapwolarsystemclasses set tag = "Unknown" where id = 0;