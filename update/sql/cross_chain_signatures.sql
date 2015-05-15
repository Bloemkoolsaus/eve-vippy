alter table mapsignatures add column authgroupid int(11) unsigned not null after chainid;
update mapsignatures s 
	inner join mapwormholechains c on c.id = s.chainid
set s.authgroupid = c.authgroupid;
alter table mapsignatures drop column chainid;