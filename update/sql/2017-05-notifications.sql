truncate users_notification;
alter table users_notification add column expiredate datetime default null after notifydate;
alter table users_notification add column persistant tinyint(1) default 0 after content;