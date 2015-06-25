alter table mapwormholechains add column countinstats tinyint(1) default 0;
update mapwormholechains set countinstats = 1;