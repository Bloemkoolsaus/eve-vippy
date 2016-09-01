create table stats_kills (
  id int(11) unsigned auto_increment,
  userid int(11) unsigned not null,
  nrkills int(11) signed default 0,
  killdate date not null,
  primary key (id),
  key `User` (userid)
);

insert into user_auth_group_config (authgroupid, var, val) values (38, 'stats_kills', 1);