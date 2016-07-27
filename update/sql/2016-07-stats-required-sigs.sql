truncate stats_kills;
alter table stats_kills add column requiredsigs int(11) unsigned default 0 after nrkills;