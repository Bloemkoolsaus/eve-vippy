create table crest_fleet (
  id int(11) unsigned not null,
  bossid int(11) unsigned not null,
  authgroupid int(11) unsigned not null,
  active tinyint(1) default 0,
  lastupdate datetime default null,
  primary key (id),
  key `CharacterBoss` (bossid),
  key `AuthorizationGroup` (authgroupid)
);