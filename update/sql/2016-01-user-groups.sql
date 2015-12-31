
truncate user_groups;
truncate user_user_group;
truncate user_rights;
truncate user_group_rights;

alter table user_groups add column authgroupid int(11) unsigned after id;
alter table user_groups drop column hidden;
alter table user_groups drop column deleted;
alter table user_groups drop column updatedate;

insert into user_groups (authgroupid, name) values (null, 'System Administrator');
insert into user_user_group (userid, groupid)
select id, 1 from users where username like '%Bloemkoolsaus%' or username like '%fixher%';
insert into user_rights (module, name, title, updatedate)
values ('admin', 'sysadmin', 'System Administrator', now());
insert into user_group_rights (groupid, rightid, level) values (1,1,1);