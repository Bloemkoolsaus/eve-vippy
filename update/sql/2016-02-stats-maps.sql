create table stats_whmap (
  id int(11) unsigned auto_increment,
  userid int(11) unsigned not null,
  corpid int(11) unsigned not null,
  pilotid int(11) unsigned default null,
  systemid int(11) unsigned not null,
  chainid int(11) unsigned not null,
  mapdate datetime,
  primary key (id)
);