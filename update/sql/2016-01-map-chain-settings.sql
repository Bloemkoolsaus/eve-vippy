create table map_chain_settings (
  chainid int(11) unsigned not null,
  var varchar(255) not null,
  val text,
  primary key (chainid, var),
  key `Chain` (chainid),
  key `Settings` (var)
);

insert into map_chain_settings (chainid, var, val)
select  id, 'directors-only', dironly
from    mapwormholechains
where   dironly > 0;

insert into map_chain_settings (chainid, var, val)
select  id, 'count-statistics', countinstats
from    mapwormholechains
where   countinstats > 0;

insert into map_chain_settings (chainid, var, val)
select  id, 'wh-autoname-scheme', autoname_whs
from    mapwormholechains
where   autoname_whs > 0;

alter table mapwormholechains drop column dironly;
alter table mapwormholechains drop column countinstats;
alter table mapwormholechains drop column autoname_whs;