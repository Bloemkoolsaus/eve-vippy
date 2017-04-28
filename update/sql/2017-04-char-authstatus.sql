alter table characters add column authstatus tinyint(1) default 0 after userid;
alter table characters add column authmessage varchar(500) default null after authstatus;
delete from characters where userid = 0 or name is null or LENGTH(trim(name)) = 0;
alter table characters drop column lastonline;