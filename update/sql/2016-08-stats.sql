create table stats_users (
  userid int(11) unsigned not null,
  corporationid int(11) unsigned not null,
  authgroupid int(11) unsigned not null,
  year int(11) unsigned not null,
  month int(11) unsigned not null,
  nrsigs int(11) default 0,
  nrwormholes int(11) default 0,
  nrkills int(11) default 0,
  reqsigs int(11) default 0,
  hoursonline int(11) default 0,
  ratio int(11) default 0,
  score int(11) default 0,
  updatedate datetime,
  primary key (userid, authgroupid, year, month),
  key `User` (userid),
  key `Corporation` (corporationid),
  key `AuthorizationGroup` (authgroupid),
  key `year` (year),
  key `month` (month)
);

drop table stats_kills;
create table stats_kills (
  userid int(11) unsigned not null,
  killdate datetime not null,
  shiptypeid int(11) unsigned not null,
  nrkills int(11) default 0,
  primary key (userid, killdate, shiptypeid),
  key `User` (userid),
  key `Date` (killdate),
  key `ShipType` (shiptypeid)
);