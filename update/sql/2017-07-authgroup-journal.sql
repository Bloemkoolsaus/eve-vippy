create table user_auth_groups_journal (
  id int(11) unsigned auto_increment,
  authgroupid int(11) unsigned not null,
  what varchar(255) default null,
  whatid int(11) unsigned default null,
  amount bigint(20) signed not null,
  balance bigint(20) signed not null,
  description varchar(500),
  transactiondate datetime not null,
  primary key (id),
  key `Autghroup` (authgroupid),
  key `WhatDescription` (what),
  key `WhatID` (whatid),
  key `What` (what, whatid)
);

alter table user_auth_groups add column balance bigint(20) signed default 0 after name;