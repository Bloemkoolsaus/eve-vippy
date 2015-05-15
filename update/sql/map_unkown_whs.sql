
alter table mapwormholeconnections add column towormholeid int(11) unsigned default null after id;
alter table mapwormholeconnections add column fromwormholeid int(11) unsigned default null after id;

update	mapwormholeconnections c
	left JOIN mapwormholes wf ON wf.solarsystemid = c.fromsystemid and wf.chainid = c.chainid
	left JOIN mapwormholes wt ON wt.solarsystemid = c.tosystemid and wt.chainid = c.chainid
set c.fromwormholeid = wf.id,
	c.towormholeid = wt.id;

alter table mapwormholeconnections drop column fromsystemid;
alter table mapwormholeconnections drop column tosystemid;