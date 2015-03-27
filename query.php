<?php
/**
 * brAWebService
 * Copyright (c) brAthena, All rights reserved.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.
 */

# SERVICES - SELECT
DEFINE('QUERY_SELECT_ACC_CHECK', 'SELECT account_id FROM login WHERE userid = :userid', false);
DEFINE('QUERY_SELECT_ACC_LOGIN', 'SELECT account_id, userid FROM login WHERE userid = :userid AND user_pass = :user_pass and group_id <= :max_group_id', false);
DEFINE('QUERY_SELECT_CHAR_LIST', 'SELECT account_id, char_id, name, char_num, class as class_, base_level, job_level, '.
                                    'last_map, last_x, last_y, save_map, save_x, save_y, online FROM `char` WHERE account_id = :account_id ORDER BY char_num ASC', false);
# SERVICES - UPDATE
DEFINE('QUERY_UPDATE_ACC_PASSWORD', 'UPDATE login SET user_pass = :new_userpass WHERE account_id = :account_id AND '.
                                        'user_pass = :old_userpass AND group_id <= :max_group_id', false);
DEFINE('QUERY_UPDATE_ACC_SEX', 'UPDATE login SET sex = :sex WHERE account_id = :account_id AND group_id <= :max_group_id', false);
DEFINE('QUERY_UPDATE_ACC_MAIL', 'UPDATE login SET email = :new_email WHERE account_id = :account_id AND email = :old_email AND group_id <= :max_group_id', false);

DEFINE('QUERY_UPDATE_CHAR_APPEAR', 'UPDATE `char` SET hair = 0, hair_color = 0, clothes_color = 0, head_top = 0, '.
                                    'head_mid = 0, head_bottom = 0 WHERE account_id = :account_id AND char_id = :char_id AND online = 0', false);
DEFINE('QUERY_UPDATE_CHAR_POSITION', 'UPDATE `char` SET last_map = save_map, last_x = save_x, last_y = save_y WHERE '.
                                        'account_id = :account_id AND char_id = :char_id AND online = 0', false);
# SERVICES - INSERT
DEFINE('QUERY_INSERT_ACC_NEW', 'INSERT INTO login (userid, user_pass, sex, email) VALUES (:userid, :user_pass, :sex, :email)', false);

# APIKEY - ALL
DEFINE('QUERY_SELECT_APIKEY_DATA', 'SELECT * FROM brawbkeys WHERE ApiKey = :ApiKey', false);
DEFINE('QUERY_UPDATE_APIKEY_COUNT', 'UPDATE brawbkeys SET ApiUsedCount = ApiUsedCount + 1 WHERE ApiKey = :ApiKey AND '.
                                    '(ApiUnlimitedCount = 1 or (ApiUsedCount < ApiLimitCount AND date() <= ApiExpires))', false);

?>
