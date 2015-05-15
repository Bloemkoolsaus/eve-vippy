alter table mapwormholejumplog add column chainid int(11) default null after connectionid;
alter table mapwormholejumplog add column fromsystemid int(11) default null after chainid;
alter table mapwormholejumplog add column tosystemid int(11) default null after fromsystemid;
alter table mapwormholejumplog drop index `character`;