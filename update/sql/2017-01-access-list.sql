create table user_accesslist (
  id int(11) unsigned not null auto_increment,
  ownerid int(11) unsigned not null,
  title varchar(255) default null,
  primary key (id)
);

create table user_accesslist_user (
  accesslistid int(11) unsigned not null,
  userid int(11) unsigned not null,
  admin tinyint(1) default 0,
  primary key (accesslistid, userid),
  key `AccessList` (accesslistid),
  key `User` (userid)
);

create table user_accesslist_characters (
  accesslistid int(11) unsigned not null,
  characterid int(11) unsigned not null,
  primary key (accesslistid, characterid),
  key `AccessList` (accesslistid),
  key `Character` (characterid)
);

create table user_accesslist_corporation (
  accesslistid int(11) unsigned not null,
  corporationid int(11) unsigned not null,
  primary key (accesslistid, corporationid),
  key `AccessList` (accesslistid),
  key `Corporation` (corporationid)
);

create table user_accesslist_alliance (
  accesslistid int(11) unsigned not null,
  allianceid int(11) unsigned not null,
  primary key (accesslistid, allianceid),
  key `AccessList` (accesslistid),
  key `Alliance` (allianceid)
);

create table mapwormholechains_accesslists (
  chainid int(11) unsigned not null,
  accesslistid int(11) unsigned not null,
  primary key (chainid, accesslistid),
  key `Map` (chainid),
  key `AccessList` (accesslistid)
)