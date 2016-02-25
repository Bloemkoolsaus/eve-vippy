create table users_auth_groups_users (
  authgroupid int(11) unsigned not null,
  userid int(11) unsigned not null,
  allowed tinyint(1) default 0,
  primary key (authgroupid, userid)
);