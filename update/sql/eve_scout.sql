create table eve_scout (
	fromsystemid int(11) unsigned not null,
	tosystemid int(11) unsigned not null,
	fromsignature varchar(50),
	tosignature varchar(50),
	updatedate datetime,
	primary key (fromsystemid, tosystemid),
	key `From` (fromsystemid),
	key `To` (tosystemid)
);