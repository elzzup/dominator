<?php

class Psychopass extends CI_Controller {

    /** @var User_model */
    public $user;

    /** @var Twitter_user_Model */
    public $userdb;
    public $is_debug;
    private $lib;

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'user');
        $this->user->set_check_login(MODE_PSYCHOPASS);
        $this->load->model('Twitter_user_model', 'userdb', TRUE);
    }

    public function index() {
        redirect(base_url());
    }

    public function p_pre($screen_name = NULL, $arg2 = "") {
        $user = $this->user->get_user(MODE_PSYCHOPASS);
        $meta = new Metaobj();
        $meta->set_title('ロード中');
        $this->load->view('head', array('meta' => $meta, 'main_css' => 'ps'));
        $this->load->view('navbar', array('meta' => $meta, 'user' => $user));
        $this->load->view('loading');
        $this->load->view('foot', array('meta' => $meta, 'is_foundationl' => TRUE, 'redirect_url' => base_url(PATH_P . $screen_name . '/' . $arg2)));
    }

    public function p($screen_name = NULL, $arg2 = "") {
        if (!isset($screen_name)) {
            redirect(base_url());
        }
        if (($rsn = $this->input->get('sn'))) {
            redirect(base_url(PATH_P_PRE . $rsn));
        }
        if (isset($screen_name) && 'sync_point' === $screen_name && isset($arg2)) {
            $this->sync_point($arg2);
        }
        $user = $this->user->get_user(MODE_PSYCHOPASS);
//        if ($this->input->get('d') !== FALSE) {
//            echo 'test';
//            $this->multi($user);
//        }
        $meta = new Metaobj();
        $meta->setup_psychopass();
        $messages = $this->_get_messages();

        $this->load->view('head', array('meta' => $meta, 'main_css' => 'ps'));
        $this->load->view('navbar', array('meta' => $meta, 'user' => $user));
        $this->load->view('alert', array('messages' => $messages));
        $u = $this->get_twitter_user($user, $screen_name, TRUE);
        $u = $this->get_twitter_user($user, $screen_name, TRUE);
        $this->load->view('psychopassuser', array('me' => $user, 'user' => $u));
        $this->load->view('foot', array('meta' => $meta, 'is_foundationl' => TRUE));
    }

    public function multi(Userobj $user) {

        ini_set("memory_limit", "-1");
        echo '<pre>';
        foreach ($user->get_friends()->ids as $id) {
            $this->get_twitter_user($user, $id);
        }
        exit;
    }

    public function sync_point($user_id) {
        if (!$user = $this->user->get_user(MODE_PSYCHOPASS)) {
            $val = array();
            $val['errors'] = 'no login error';
            $this->load->view('json_value', array('value' => $val));
            exit;
        }
        $u = $this->get_twitter_user($user, $user_id);
        $u->compact();
        $this->load->view('json_value', array('value' => array($u)));
    }

    public function init_my_followers() {
        $user = $this->user->get_user(MODE_PSYCHOPASS);
        $ids = $user->get_friends_all()->ids;
        array_chunk($ids, 100);
    }

    /**
     * 
     * @param type $user_id
     * @param type $is_screen_name
     * @return Userinfoobj
     */
    private function get_twitter_user($user, $user_id, $is_screen_name = FALSE) {
        if (($is_screen_name && preg_match("#arzzup#i", $user_id)) || $user_id == ADMIN_TWITTER_ID) {
            $u = new Userinfoobj();
            $u->user_id = ADMIN_TWITTER_ID;
            $u->screen_name = "Arzzup";
            $u->score = 0;
            $u->max_score = "999.9";
            return $u;
        }
        $reco = $this->userdb->load_user($user_id, $is_screen_name);
        $g = $this->input->get(GET_DEBUG_USER);
        if (!$reco) {
            // no user
            if (!isset($user)) {
                return null;
            }
            $statuses = $user->get_user_timeline($user_id, $is_screen_name);
            $u = $this->_analize_one($statuses);
            $this->userdb->regist_user($u);
        } else {
            $u = new Userinfoobj();
            $u->set_user($reco);
            if (!$u->is_recent() && isset($user)) {
                $statuses = $user->get_user_timeline($user_id, $is_screen_name);
                $u_pre = $u;
                $u = $this->_analize_one($statuses);
                $u->reflect_recent($u_pre);
                $this->userdb->update_user($u);
            }
        }
        if (isset($user) && isset($statuses)) {
            $u->support_user($statuses[0]->user);
        }
        return $u;
    }

    private function _analize_one($statuses) {
        $user = NULL;
        $igo = new Igo(PATH_LIB_IGO_DICT);

        $words = array();
        foreach ($statuses as $st) {
            if (!isset($user)) {
                $user = new Userinfoobj($st->user);
            }
            $words = array_merge($words, $igo->wakati(preg_replace('#@[a-z_]+#', '', $st->text)));
        }
        $p = $this->negaposi($words);
        $user->set_point($p);
        return $user;
    }

    private function negaposi($words) {
        if (!isset($this->lib)) {
            $this->load_lib();
        }

        $p_sum = 0;
//        echo '<pre>';
        foreach ($words as $w) {
            if (preg_match('#^[ぁ-ん！？ー]$#u', $w)) {
                continue;
            }

            if (isset($this->lib[$w])) {
//                echo $w . ':' . $this->lib[$w];
//                echo PHP_EOL;
                $p_sum += $this->lib[$w];
            }
        }

//        echo $p_sum . PHP_EOL;
        return $p_sum * 1.1 * 100 / PS_USER_TWEET_NUM;
    }

    private function load_lib() {
        $lib = array();
        foreach (explode("\n", trim(file_get_contents(PATH_LIB_PN_JP_N))) as $line) {
            list ($name, $name_kana, $p, $point) = explode(':', $line);
            $lib[$name] = $point;
            if ($name == $name_kana || preg_match('#:[ぁ-ん]{,2}:#u', $name_kana)) {
                continue;
            }
            $lib[$name_kana] = $point;
        }
        foreach (explode("\n", trim(file_get_contents(PATH_LIB_PN_EN_N))) as $line) {
            list ($name, $p, $point) = explode(':', $line);
            $lib[$name] = $point;
            if ($name == $name_kana) {
                continue;
            }
            $lib[$name_kana] = $point;
        }
        $this->lib = $lib;
    }

    private function _get_messages() {
        $messages = array();
        if (($err = $this->session->userdata('err'))) {
            $this->session->unset_userdata('err');
            $messages[] = $err;
        }
        if (($posted = $this->session->userdata('posted'))) {
            $this->session->unset_userdata('posted');
            $messages[] = $posted;
        }
        return $messages;
    }

    public function migrate($version) {
        $this->load->library('migration');

        if ($this->migration->version($version)) {
            log_message('error', 'Migration Success.');
        } else {
            log_message('error', $this->migration->error_string());
        }
    }

}
