update system_config set var = 'sso_login_url' where var = 'crest_login_url';
update system_config set var = 'sso_callback_url' where var = 'crest_callback_url';
update system_config set var = 'sso_clientid' where var = 'crest_clientid';

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
  `requesttype` varchar(100) DEFAULT 'get',
  `url` varchar(500) NOT NULL,
  `httpstatus` int(11) DEFAULT NULL,
  `content` text,
  `response` text,
  `requestdate` datetime NOT NULL,
  KEY `httpstatus` (`httpstatus`),
  KEY `requestdate` (`requestdate`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

insert into system_config (var, val) values ('esi_url', 'https://esi.tech.ccp.is/');
alter table alliances add column ticker varchar(255) default null after name;