create table crest_fleet_member (
  fleetid int(11) unsigned not null,
  characterid int(11) unsigned not null,
  wingid bigint(20) unsigned default null,
  squadid bigint(20) unsigned default null,
  solarsystemid bigint(20) unsigned default null,
  shiptypeid bigint(20) unsigned default null,
  takewarp tinyint(1) default 0,
  primary key (fleetid, characterid)
);