update system_config set var = 'sso_login_url' where var = 'crest_login_url';
update system_config set var = 'sso_callback_url' where var = 'crest_callback_url';
update system_config set var = 'sso_clientid' where var = 'crest_clientid';
insert into system_config (var, val) values ('esi_url', 'https://esi.tech.ccp.is/');
alter table alliances add column ticker varchar(255) default null after name;
alter table users drop column password;


CREATE TABLE `sso_token` (
  `tokentype` varchar(255) NOT NULL,
  `tokenid` bigint(11) unsigned NOT NULL,
  `ownerhash` varchar(500) DEFAULT NULL,
  `state` varchar(500) DEFAULT NULL,
  `accesstoken` varchar(500) DEFAULT NULL,
  `refreshtoken` varchar(500) DEFAULT NULL,
  `expires` varchar(500) DEFAULT NULL,
  `scopes` text,
  `updatedate` datetime DEFAULT NULL,
  PRIMARY KEY (`tokentype`,`tokenid`),
  KEY `TokenType` (`tokentype`),
  KEY `Identifier` (`tokenid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `esi_log` (
  `id` bigint(11) auto_increment,
  `requesttype` varchar(100) DEFAULT 'get',
  `url` varchar(500) NOT NULL,
  `requestdate` datetime NOT NULL,
  `expiredate` datetime not null,
  `httpstatus` int(11) DEFAULT NULL,
  `content` text,
  `response` text,
  primary key (id),
  KEY `httpstatus` (`httpstatus`),
  KEY `requestdate` (`requestdate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `esi_fleet` (
  `id` bigint(11) unsigned NOT NULL,
  `bossid` int(11) unsigned NOT NULL,
  `authgroupid` int(11) unsigned NOT NULL,
  `active` tinyint(1) DEFAULT '0',
  `statusmessage` text,
  `lastupdate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `CharacterBoss` (`bossid`),
  KEY `AuthorizationGroup` (`authgroupid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `esi_fleet_member` (
  `fleetid` bigint(20) unsigned NOT NULL,
  `characterid` bigint(20) unsigned NOT NULL,
  `wingid` bigint(20) unsigned DEFAULT NULL,
  `squadid` bigint(20) unsigned DEFAULT NULL,
  `solarsystemid` bigint(20) unsigned DEFAULT NULL,
  `shiptypeid` bigint(20) unsigned DEFAULT NULL,
  `takewarp` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`fleetid`,`characterid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

drop table crest_character_location;
drop table crest_fleet;
drop table crest_fleet_member;
drop table crest_log;
drop table crest_token;