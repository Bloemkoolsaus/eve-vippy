create table vippy_api_key (
  id int(11) unsigned auto_increment,
  authgroupid int(11) unsigned not null,
  description varchar(255) default null,
  apikey varchar(255) default null,
  deleted tinyint(1) default 0,
  primary key (id),
  key `key` (apikey)
);

create table vippy_api_log (
  id int(11) unsigned auto_increment,
  apikeyid int(11) unsigned not null,
  logdate datetime not null,
  url varchar(500),
  ipaddress varchar(255),
  info text,
  primary key (id)
);

create table vippy_api_ips (
  apikeyid int(11) unsigned not null,
  ipaddress varchar(255) not null,
  primary key (apikeyid, ipaddress)
);

insert into vippy_api_key (id, authgroupid, description, apikey, deleted) values (1, 38, 'stats.limited-power.co.uk', 'aGVsbG9teW5hbWVpc2ZsaWdodHk=', 0);
insert into vippy_api_ips (1, '104.28.26.30');