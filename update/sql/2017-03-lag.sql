alter table map_signature add index `UpdateDate` (updatedate);
alter table map_signature add index `ChainSolarsystem` (authgroupid, solarsystemid);

-- Fix positioning bug
update system_config set val = 20 where var = 'map_wormhole_offset_x';