create table stats_kills (
  id int(11) unsigned auto_increment,
  userid int(11) unsigned not null,
  nrkills int(11) signed default 0,
  primary key (id),
  key `User` (userid)
);