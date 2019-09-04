<?php
/*
	This file maps foreign keys.
*/

$pkey['attachments']="attach_id";
$fkey['attachments']=array(
	"post_msg_id" => "posts",
	"topic_id" => "topics",
	"poster_id" => "users"
);

$pkey['banlist']="ban_id";

$pkey['drafts']="draft_id";
$fkey['drafts']=array(
	"user_id" => "users",
	"topic_id" => "topics",
	"forum_id" => "forums"
);

$pkey['forums']="forum_id";
$fkey['forums']=array(
	"parent_id" => "forums",
	"left_id" => "forums",
	"right_id" => "forums"
);

$fkey['forums_access']=array(
	"forum_id" => "forums",
	"user_id" => "users"
);

$fkey['forums_track']=array(
	"forum_id" => "forums",
	"user_id" => "users"
);

$fkey['forums_watch']=array(
	"forum_id" => "forums",
	"user_id" => "users"
);


$pkey['log']="log_id";
$fkey['log']=array(
	"user_id" => "users",
	"forum_id" => "forums",
	"topic_id" => "topics",
	"reportee_id" => "users"
);

$pkey['poll_options']="poll_option_id";
$fkey['poll_options']=array(
	"topic_id" => "topics"
);

$fkey['poll_votes']=array(
	"topic_id" => "topics",
	"poll_option_id" => "poll_options"
);

$pkey['posts']="post_id";
$fkey['posts']=array(
	"topic_id" => "topics",
	"forum_id" => "forums",
	"poster_id" => "users",
	"icon_id" => 0
);

$pkey['privmsgs']="msg_id";
$fkey['privmsgs']=array(
	"author_id" => "users",
	"icon_id" => 0
);

$pkey['privmsgs_folder']="folder_id";
$fkey['privmsgs_folder']=array(
	"user_id" => "users"
);

$pkey['privmsgs_rules']="rule_id";
$fkey['privmsgs_rules']=array(
	"user_id" => "users"
);

$fkey['privmsgs_to']=array(
	"msg_id" => "privmsgs",
	"user_id" => "users",
	"author_id" => "users",
	"folder_id" => "privmsgs_folder"
);

$pkey['ranks']="rank_id";

$pkey['topics']="topic_id";
$fkey['topics']=array(
	"forum_id" => "forums",
	"icon_id" => 0,
	"topic_first_post_id" => "posts",
	"topic_last_post_id" => "posts",
	"topic_last_poster_id" => "users",
	"topic_moved_id" => "topics"
);

$fkey['topics_posted']=array(
	"user_id" => "users",
	"topic_id" => "topics"
);

$fkey['topics_track']=array(
	"user_id" => "users",
	"topic_id" => "topics",
	"forum_id" => "forums"
);

$fkey['topics_posted']=array(
	"user_id" => "users",
	"topic_id" => "topics"
);

$pkey['users']="user_id";
$fkey['users']=array(
	"user_rank" => "ranks"
);

$pkey['warnings']="warning_id";
$fkey['warnings']=array(
	"user_id" => "users",
	"post_id" => "posts",
	"log_id" => "log"
);

$fkey['zebra']=array(
	"user_id" => "users",
	"zebra_id" => "users"
);

?>
