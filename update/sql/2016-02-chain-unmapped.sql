insert into map_chain_settings (chainid, var, val)
select id, 'create-unmapped', 1 from mapwormholechains where authgroupid not in (46);