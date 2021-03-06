<?php

class Twitter_user_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function regist_user(Userinfoobj $user) {
        $this->insert_user($user);
    }

    private function insert_user(Userinfoobj $user) {
        $data = array(
            DB_CN_USERS_TWITTER_USER_ID => $user->user_id,
            DB_CN_USERS_PRE_SCORE => $user->score * PS_DB_SHIFT,
            DB_CN_USERS_MAX_SCORE => $user->score * PS_DB_SHIFT,
            DB_CN_USERS_TWITTER_SCREEN_NAME => $user->screen_name,
            DB_CN_USERS_IMG_URL => $user->img_path,
        );
        return $this->db->insert(DB_TN_USERS, $data);
    }

    public function update_user(Userinfoobj $user) {
        $this->db->set(DB_CN_USERS_PRE_SCORE, $user->score * PS_DB_SHIFT);
        $this->db->set(DB_CN_USERS_MAX_SCORE, $user->max_score * PS_DB_SHIFT);
        $this->db->set(DB_CN_USERS_IMG_URL, $user->img_path);
        $this->db->set(DB_CN_USERS_TWITTER_SCREEN_NAME, $user->screen_name);
        $this->db->where(DB_CN_USERS_TWITTER_USER_ID, $user->user_id);
        $this->db->update(DB_TN_USERS);
    }

    public function load_user($twitter_user_id, $is_screen_name = FALSE) {
        $res = $this->select_user($twitter_user_id, $is_screen_name);
        return !empty($res) ? $res[0] : NULL;
    }

    public function load_recent_users() {
        return wrap_users($this->select_recent_users());
    }

    private function select_user($twitter_user_id, $is_screen_name = FALSE) {
        if ($is_screen_name) {
            $this->db->where(DB_CN_USERS_TWITTER_SCREEN_NAME, $twitter_user_id);
        } else {
            $this->db->where(DB_CN_USERS_TWITTER_USER_ID, $twitter_user_id);
        }
        $this->db->limit(1);
        $query = $this->db->get(DB_TN_USERS);
        return $query->result();
    }

    private function select_recent_users() {
        $this->db->order_by(DB_CN_USERS_LAST_UPDATE, 'desc');
        $this->db->limit(PS_TOP_USER_NUM);
        $query = $this->db->get(DB_TN_USERS);
        return $query->result();
    }


}
