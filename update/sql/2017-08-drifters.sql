create table notices_drifter (
  solarsystemid int(11) unsigned not null,
  authgroupid int(11) unsigned not null,
  nrdrifters int(11) unsigned default 0,
  comments varchar(500) default null,
  updateby int(11) unsigned default null,
  updatedate datetime default null,
  primary key (solarsystemid, authgroupid),
  key `SolarSystem` (solarsystemid),
  key `AuthGroup` (authgroupid)
);