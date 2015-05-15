alter table mapwormholes add column fullyscanned datetime default null after adddate;
alter table mapwormholes add column fullyscannedby int(11) unsigned default null after fullyscanned;