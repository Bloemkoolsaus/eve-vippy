alter table vippy_subscriptions_journal add column approved tinyint(1) default 0;
alter table vippy_subscriptions_journal add column deleted tinyint(1) default 0;
update vippy_subscriptions_journal set approved = 1;

delete from user_auth_groups where id not in (select authgroupid from mapwormholechains);
delete from user_auth_groups_alliances where authgroupid not in (select id from user_auth_groups);
delete from user_auth_groups_corporations where authgroupid not in (select id from user_auth_groups);