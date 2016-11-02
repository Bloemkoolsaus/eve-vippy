alter table stats_whmap add column authgroupid int(11) unsigned not null after chainid;

update stats_whmap s
  inner join mapwormholechains c on c.id = s.chainid
set s.authgroupid = c.authgroupid;

alter table stats_whmap drop column chainid;