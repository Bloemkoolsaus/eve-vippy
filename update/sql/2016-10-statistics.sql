update user_log
set what = 'ingame', whatid = pilotid
where what = 'login' and pilotid != 0 and pilotid is not null;

update user_log
set pilotid = NULL
where pilotid = 0;

update user_log
set whatid = NULL
where whatid = 0;