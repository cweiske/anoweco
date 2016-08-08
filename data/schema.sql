


CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_user_id` int(11) NOT NULL,
  `comment_published` datetime NOT NULL,
  `comment_of_url` varchar(2048) NOT NULL,
  `comment_type` varchar(32) NOT NULL,
  `comment_json` mediumtext NOT NULL,
  `comment_pingstate` varchar(6) NOT NULL,
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(128) NOT NULL,
  `user_imageurl` varchar(1024) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


