alter table vippy_subscriptions drop column checkpayments;
alter table vippy_subscriptions drop column totaldue;
alter table vippy_subscriptions drop column totalpayed;
alter table vippy_subscriptions add column resetbalance tinyint(1) default 0;

update vippy_subscriptions set tilldate = '2017-04-30 23:59:59' where tilldate is null or tilldate > '2017-04-30';
insert into vippy_subscriptions (authgroupid, description, amount, fromdate, tilldate, resetbalance)
select id, 'Vippy 5 year anniversary', 0, '2017-05-01 00:00:00', '2017-08-31 23:59:59', 1 from user_auth_groups where deleted = 0;

alter table user_auth_groups drop column contactuserid;