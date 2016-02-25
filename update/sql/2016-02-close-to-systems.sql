create table map_closest_systems (
  solarsystemid int(11) unsigned not null,
  authgroupid int(11) unsigned not null,
  userid int(11) unsigned default null,
  showonmap tinyint(1) unsigned default 0,
  primary key (solarsystemid, authgroupid, userid)
);

drop table mapclosesttradehub;