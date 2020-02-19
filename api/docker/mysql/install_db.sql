# lasertag_device
# ------------------------------------------------------------
CREATE TABLE `lasertag_device`
(
    `id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name`      varchar(255)          DEFAULT NULL,
    `ip`        varchar(80)           DEFAULT NULL,
    `createdon` timestamp        NULL DEFAULT CURRENT_TIMESTAMP,
    `updatedon` timestamp        NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

# lasertag_device_log
# ------------------------------------------------------------
CREATE TABLE `lasertag_device_log`
(
    `id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
    `device`    int(11)               DEFAULT NULL,
    `value`     varchar(255)          DEFAULT NULL,
    `createdon` timestamp        NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

# lasertag_game
# ------------------------------------------------------------
CREATE TABLE `lasertag_game`
(
    `id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name`      varchar(255)          DEFAULT NULL,
    `starttime` timestamp        NULL DEFAULT CURRENT_TIMESTAMP,
    `endtime`   timestamp        NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;