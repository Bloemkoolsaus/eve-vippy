create table crest_character_location (
  characterid int(11) unsigned not null,
  solarsystemid int(11) unsigned not null,
  lastupdate datetime not null,
  primary key (characterid)
);