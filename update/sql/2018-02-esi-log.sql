drop table esi_log;

create table esi_log (
  requesttype varchar(100) default 'get',
  url varchar(500) not null,
  requestdate datetime not null,
  expiredate datetime not null,
  httpstatus int(11) default null,
  errorremain int(11) default null,
  errorreset int(11) default null,
  content text,
  response text,
  key url (url),
  key httpstatus (httpstatus),
  key requestdate (requestdate)
) engine=InnoDB;

create table esi_status (
  enabled tinyint(1) default 1,
  errorremain int(11) default null,
  errorreset int(11) default null,
  updatedate datetime default null
);
insert into esi_status (enabled, errorremain, errorreset, updatedate) values (1, 100, 1, now());