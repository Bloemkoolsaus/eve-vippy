insert into map_chain_settings (chainid, var, val)
select id, 'auto-expiry', 1 from mapwormholechains where deleted = 0;