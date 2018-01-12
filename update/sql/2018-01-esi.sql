update system_config set var = 'sso_login_url' where var = 'crest_login_url';
update system_config set var = 'sso_callback_url' where var = 'crest_callback_url';
update system_config set var = 'sso_clientid' where var = 'crest_clientid';

alter table users drop column password;