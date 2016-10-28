create table map_anomaly (
  id int(11) unsigned auto_increment,
  typeid int(11) unsigned default null,
  authgroupid int(11) unsigned not null,
  solarsystemid int(11) unsigned not null,
  signatureid varchar(10),
  description varchar(255),
  primary key (id),
  key `SignatureType` (typeid),
  key `AuthGroup` (authgroupid),
  key `SolarSystem` (solarsystemid)
);

create table map_anomaly_type (
  id int(11) unsigned auto_increment,
  type varchar(255),
  name varchar(255),
  primary key (id)
);

drop table mapanomalies;
drop table mapanomalies_types;