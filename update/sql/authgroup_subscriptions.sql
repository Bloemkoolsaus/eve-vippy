create table vippy_subscriptions (
	id int(11) unsigned auto_increment,
	authgroupid int(11) unsigned not null,
	description text,
	amount int(11) unsigned default 0,
	fromdate datetime default null,
	tilldate datetime default null,
	primary key (id),
	key `AuthGroup` (authgroupid)
);

insert into vippy_subscriptions (authgroupid, description, amount, fromdate, tilldate)
	select 	id, 'Vippy License', 0, validfrom, IF(validtill='2020-12-31',null,validtill)
	from 	user_auth_groups;

update vippy_subscriptions set fromdate = '2015-04-01' where tilldate is null;

update vippy_subscriptions set amount = 500 where authgroupid = 22;
update vippy_subscriptions set amount = 100 where authgroupid = 28;
update vippy_subscriptions set amount = 300 where authgroupid = 27;
update vippy_subscriptions set amount = 300 where authgroupid = 26;
update vippy_subscriptions set amount = 700, description = 'Vippy + Atlas License' where authgroupid = 23;
update vippy_subscriptions set amount = 800, description = 'Vippy + Atlas License' where authgroupid = 14;
update vippy_subscriptions set amount = 100 where authgroupid = 24;

alter table user_auth_groups drop column validfrom;
alter table user_auth_groups drop column validtill;