insert into system_config (var, val) select 'system_payments_api_keyid', val from config where var = 'wallet_api_keyid';
insert into system_config (var, val) select 'system_payments_api_vcode', val from config where var = 'wallet_api_vcode';
insert into system_config (var, val) select 'system_payments_characterid', val from config where var = 'wallet_api_charid';
delete from config where var like 'wallet%';
alter table vippy_subscriptions_journal add column fromcorporationid int(11) unsigned default null after fromcharacterid;