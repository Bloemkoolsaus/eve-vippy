create table map_signature (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solarsystemid` int(11) DEFAULT NULL,
  `authgroupid` int(11) unsigned NOT NULL,
  `sigid` varchar(255) DEFAULT NULL,
  `sigtypeid` varchar(255) DEFAULT NULL,
  `whtypeid` int(11) DEFAULT NULL,
  `signalstrength` varchar(255) DEFAULT NULL,
  `siginfo` varchar(255) DEFAULT NULL,
  `scandate` datetime DEFAULT NULL,
  `updatedate` datetime DEFAULT NULL,
  `scannedby` int(11) DEFAULT NULL,
  `updateby` int(11) DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `System` (`solarsystemid`),
  KEY `AuthGroup` (`authgroupid`)
);

create table map_signature_type (
  id int(11) unsigned auto_increment,
  name varchar(255) not null,
  description text,
  primary key (id)
);
insert into map_signature_type (name, description) values
('data','Data site'),
('gas','Gas site'),
('relic','Relic site'),
('combat','Combat site'),
('wh','Wormhole'),
('pos','Player Owned Starbase'),
('citadel','Citadel');


insert into map_signature (solarsystemid, authgroupid, sigid, sigtypeid, whtypeid, signalstrength,
                           siginfo, scandate, updatedate, scannedby, updateby, deleted)
select  s.solarsystemid, s.authgroupid, s.sigid, t.id, s.typeid, s.signalstrength,
        s.siginfo, s.scandate, s.updatedate, s.scannedby, s.updateby, s.deleted
from    mapsignatures s
  left join map_signature_type t on t.name = s.sigtype
where   s.deleted = 0;

drop table mapsignatures;
drop table mapsiglistcache;