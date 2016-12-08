alter table vippy_subscriptions add column totaldue int(11) unsigned default null;
alter table vippy_subscriptions add column totalpayed int(11) unsigned default null;
alter table vippy_subscriptions add column checkpayments tinyint(1) default 1;

update vippy_subscriptions set checkpayments = 0;