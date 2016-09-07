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
('crest_accept_version', 'application/vnd.ccp.eve.character-v4+json');