create table map_knownwormhole (
  solarsystemid int(11) unsigned not null,
  authgroupid int(11) unsigned not null,
  name varchar(255) default null,
  status int(11) signed default 0,
  primary key (solarsystemid,authgroupid),
  key `SolarSystem` (solarsystemid),
  key `AuthGroup` (authgroupid)
);

insert into map_knownwormhole (solarsystemid, authgroupid, name, status)
select  solarsystemid, authgroupid, name, status
from    mapknownwormholes
where   length(name) > 0
group by solarsystemid, authgroupid;

drop table mapknownwormholes;