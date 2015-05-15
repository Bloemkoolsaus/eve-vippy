delete from mapwormholechains where authgroupid is null or authgroupid = 0;
alter table mapwormholechains modify column authgroupid int(11) unsigned not null;
delete from notices where authgroupid is null or authgroupid = 0;