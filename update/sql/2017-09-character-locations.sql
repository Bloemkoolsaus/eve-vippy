drop table map_character_locations;
create table map_character_locations (
  characterid int(11) unsigned not null,
  solarsystemid int(11) unsigned default 0,
  shiptypeid int(11) unsigned default 0,
  lastdate datetime not null,
  primary key (characterid)
);