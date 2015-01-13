<?php
/* @var $user Userinfoobj */
?>
<div class="row">
    <div class="small-12 small-centered medium-10 medium-push-1">
        <?php if (isset($user)) { ?>
            <h1 class="user-title">@<?= $user->screen_name ?>のサイコパス</h1>
            <div class="row user-on">
                <div class="small-5 medium-3 columns">
                    <img src="<?= $user->img_path ?>" alt="">
                    <p>
                        <a href="//twitter.com/<?= $user->screen_name ?>" target="_blank">@<?= $user->screen_name ?></a>
                    </p>
                </div>
                <div class="small-7 medium-6 columns">
                    <p>
                        犯罪係数
                        <br class="show-for-small-only">
                        <span class="point"><?= $user->score ?></span>
                    </p>
                    <p>
                        最高犯罪係数<?= $user->max_score ?>
                    </p>
                    <div class="hidden-for-small color-ribbon" style="background: url(<?= base_url("./images/color.png") ?>) <?= floor($user->score / 10) ?>% 0;"></div>
                </div>
                <div class="hidden-for-small medium-3 columns">
                    <!--TODO:-->
                    <a href="<?= generate_share_url_twitter(base_url(PATH_P . $user->screen_name), generate_dominator_text($user->score, $user->screen_name)) ?>" class="button large">執行する<br>(ツイート)</a>
                </div>
            </div>
            <div class="color-ribbon show-for-small-only" style="background: url(<?= base_url("./images/color.png") ?>) <?= floor($user->score / 10) ?>% 0;"></div>
            <div class="row show-for-small-only top-margin">
                <div class="small-8 small-centered columns">
                    <!--TODO:-->
                    <a href="<?= generate_share_url_twitter(base_url(PATH_P . $user->screen_name), generate_dominator_text($user->score, $user->screen_name)) ?>" class="button expand large">執行する<br>(ツイート)</a>
                </div>
            <?php } else { ?>
                <h2>ユーザを取得できませんでした</h2>
            <?php } ?>
        </div>
    </div>
    <?php $this->load->view('psychopassform'); ?>