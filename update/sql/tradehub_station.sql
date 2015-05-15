create table maptradehubs (
	solarsystemid int(11) unsigned not null,
	stationid int(11) unsigned not null,
	primary key (solarsystemid)
);

insert into maptradehubs (solarsystemid, stationid) values
	(30000142, 60003760),
	(30002053, 60005686),
	(30002187, 60008494),
	(30002510, 60004588),
	(30002659, 60011866);