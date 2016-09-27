drop table character_roles;
drop table corptitles;
drop table chrtitles;
drop table mapsolarsystemjumps;
drop table mapwormholecharacterlocations;

create table map_character_locations (
  characterid int(11) unsigned not null,
  solarsystemid int(11) unsigned not null,
  shiptypeid int(11) unsigned not null,
  authgroupid int(11) unsigned not null,
  lastdate datetime not null,
  primary key (characterid),
  key `SolarSystem` (solarsystemid),
  key `AuthGroup` (authgroupid),
  key `UpdateDate` (lastdate)
);

alter table characters drop column api_keyid;
alter table characters drop column race;
alter table characters drop column bloodline;
alter table characters drop column ancestry;
alter table characters drop column gender;
alter table characters drop column clonename;
alter table characters drop column cloneskillpoints;
alter table characters drop column skillpoints;
alter table characters drop column balance;
alter table characters drop column perceptionbonus;
alter table characters drop column intelligencebonus;
alter table characters drop column memorybonus;
alter table characters drop column willpowerbonus;
alter table characters drop column charismabonus;
alter table characters drop column intelligence;
alter table characters drop column memory;
alter table characters drop column charisma;
alter table characters drop column perception;
alter table characters drop column willpower;
alter table characters drop column dob;

create table crest_token (
  tokentype varchar(255) not null,
  tokenid int(11) unsigned not null,
  ownerhash varchar(500) default null,
  state varchar(500) default null,
  accesstoken varchar(500) default null,
  refreshtoken varchar(500) default null,
  expires varchar(500) default null,
  scopes text default null,
  updatedate datetime,
  primary key (tokentype, tokenid),
  key `TokenType` (tokentype),
  key `Identifier` (tokenid)
);

insert into system_config (var, val) values
('crest_url', 'https://crest-tq.eveonline.com/'),
('crest_login_url', 'https://login.eveonline.com/oauth/'),
('crest_accept_version', 'application/vnd.ccp.eve.character-v4+json'),
('crest_callback_url', 'http://eve-vippy.com/crest/login'),
('crest_clientid', 'cc67fe1f7f4f41a08fab47590587e748'),
('crest_secret_key', '9m2g65oA93OZHJGC0lYX8AUbf0ZdE84GihzQZx6F');