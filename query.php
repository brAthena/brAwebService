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

# SERVICES
DEFINE('QUERY_SELECT_ACC_LOGIN', 'SELECT account_id, userid FROM login WHERE userid = :userid AND user_pass = :user_pass and group_id <= :max_group_id', false);

# APIKEY
DEFINE('QUERY_UPDATE_APIKEY_COUNT', 'UPDATE brawbkeys SET ApiUsedCount = ApiUsedCount + 1 WHERE ApiKey = :ApiKey AND '.
                                    '(ApiUnlimitedCount = 1 or (ApiUsedCount < ApiLimitCount AND date() <= ApiExpires))', false);
DEFINE('QUERY_SELECT_APIKEY_DATA', 'SELECT * FROM brawbkeys WHERE ApiKey = :ApiKey', false);

?>
