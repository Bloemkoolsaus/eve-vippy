alter table users_setting add column active tinyint(1) default 1;
update users_setting set active = 0 where name = 'whtypefield';

insert into users_setting (name, title) values ('scanalt', 'Dedicated scanning character');