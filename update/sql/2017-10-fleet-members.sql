DROP TABLE IF EXISTS eve_vippy.crest_fleet_member;
CREATE TABLE `crest_fleet_member` (
  `fleetid` bigint(20) unsigned NOT NULL,
  `characterid` bigint(20) unsigned NOT NULL,
  `wingid` bigint(20) unsigned DEFAULT NULL,
  `squadid` bigint(20) unsigned DEFAULT NULL,
  `solarsystemid` bigint(20) unsigned DEFAULT NULL,
  `shiptypeid` bigint(20) unsigned DEFAULT NULL,
  `takewarp` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`fleetid`,`characterid`)
) ;