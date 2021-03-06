<?php

class Index extends CI_Controller {

    /** @var User_model */
    public $user;
    /** @var Twitter_user_Model */
    public $userdb;

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model', 'user');
        $this->user->set_check_login(MODE_PSYCHOPASS);
        $this->load->model('Twitter_user_model', 'userdb', TRUE);
    }

    public function index() {
        if (($rsn = $this->input->get('sn'))) {
            redirect(base_url(PATH_P_PRE . $rsn));
        }
        $user = $this->user->get_user(MODE_PSYCHOPASS);
        $meta = new Metaobj();
        $meta->setup_psychopass();
        $messages = $this->_get_messages();

        $this->load->view('head', array('meta' => $meta, 'main_css' => 'ps'));
        $this->load->view('navbar', array('meta' => $meta, 'user' => $user));
        $this->load->view('alert', array('messages' => $messages));
        $users = FALSE;
        if (!isset($user)) {
            $this->load->view('psychopasslogin');
        } else {
            $statuses = $user->get_timeline();
            if ($statuses !== FALSE) {
                $users = $this->_wrap_user($statuses);
            }
        }
        $this->load->view('psychopassbody', array('users' => $users));
        $usersl = $this->userdb->load_recent_users();
        $this->load->view('psychopasslog', array('users' => $usersl));
        $this->load->view('foot', array('meta' => $meta, 'is_foundationl' => TRUE, 'jss' => array('ps_helper')));
    }

    /**
     * 
     * @param type $user_id
     * @param type $is_screen_name
     * @return Userinfoobj
     */
    private function get_twitter_user(Userobj $user, $user_id, $is_screen_name = FALSE) {
        if ($is_screen_name) {
            $statuses = $user->get_user_timeline($user_id, TRUE);
            $user_id = $statuses[0]->user->id;
        }
        $reco = $this->userdb->load_user($user_id);
        if (!$reco) {
            // no user
            if (!isset($statuses)) {
                $statuses = $user->get_user_timeline($user_id);
            }
            $u = $this->_analize_one($statuses);
            $this->userdb->regist_user($u);
        } else {
            $u = new Userinfoobj();
            $u->set_user($reco);
            if (!$u->is_recent()) {
                if (!isset($statuses)) {
                    $statuses = $user->get_user_timeline($user_id);
                }
                $u_pre = $u;
                $u = $this->_analize_one($statuses);
                $u->reflect_recent($u_pre);
                $this->userdb->update_user($u);
            }
        }
        if ($is_screen_name) {
            $u->support_user($statuses[0]->user);
        }
        return $u;
    }

    private function _wrap_user($statuses) {
        $users = array();
        foreach ($statuses as $st) {
            if (!isset($users[$st->user->id])) {
                $users[$st->user->id] = new Userinfoobj($st->user);
            }
        }

        usort($users, function(Userinfoobj $a, Userinfoobj $b) {
            return $a->count > $b->count;
        });
        $users_select = array_slice($users, 0, PS_TOP_USER_NUM);
//        foreach ($users_select as &$user) {
//            $p = $this->negaposi($user->text);
//            echo 'P: ' . $p ;
//            $user->set_point($p);
//            var_dump($user);
//        }
        return $users_select;
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
