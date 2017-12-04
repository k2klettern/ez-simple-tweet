<?php
if (!current_user_can('manage_options')) {
    wp_die(_e('You are not authorized to view this page.', 'eztweet'));
}
$option_name = 'ez_tweet_inputs';
$inputs = get_option($option_name);

if (isset( $_POST['dataapptweet'] ) && wp_verify_nonce( $_POST['dataapptweet'], 'eztweet-data' )) {

    $inputs = $_POST['inputs'];
    if ( get_option( $option_name ) !== false ) {
        $update = update_option($option_name, $inputs);
    } else {
        $deprecated = null;
        $autoload = 'no';
        $update = add_option($option_name, $inputs, $deprecated, $autoload);
    }

    if($update) {
        echo '<div class="updated">';
        _e('settings saved.', 'eztweet');
        echo '</strong></p></div>';
    } else {
        echo '<div class="error"><p><strong>';
        _e('Error - Url does not seems to be correct.', 'eztweet');
        echo '</strong></p></div>';
    }
}

?>
<div class="wrap">
    <h1>EZ Simple Tweet</h1>
    <span><?php _e('by Eric Zeidan','eztweet'); ?></span>
    <h4><?php _e('Insert your App Tweet Data', 'eztweet');?></h4>
        <form name="form1" method="post" action="">
            <?php wp_nonce_field( 'eztweet-data', 'dataapptweet' ); ?>
            <p>
            <label for="oauth_access_token"><?php _e('Oauth access token', 'eztweet');?></label>
            <input type="text" name="inputs[oauth_access_token]" class="regular-text" id="oauth_access_token" value="<?php if(isset($inputs['oauth_access_token'])) echo $inputs['oauth_access_token'];?>">
            </p>
            <p>
                <label for="oauth_access_token_secret"><?php _e('Oauth access token secret', 'eztweet');?></label>
                <input type="text" name="inputs[oauth_access_token_secret]" class="regular-text" id="oauth_access_token_secret" value="<?php if(isset($inputs['oauth_access_token'])) echo $inputs['oauth_access_token_secret'];?>">
            </p>
            <p>
                <label for="consumer_key"><?php _e('Consumer key', 'eztweet');?></label>
                <input type="text" name="inputs[consumer_key]" class="regular-text" id="consumer_key" value="<?php if(isset($inputs['oauth_access_token'])) echo $inputs['consumer_key'];?>">
            </p>
            <p>
                <label for="consumer_secret"><?php _e('Consumer secret', 'eztweet');?></label>
                <input type="text" name="inputs[consumer_secret]" class="regular-text" id="consumer_secret" value="<?php if(isset($inputs['oauth_access_token'])) echo $inputs['consumer_secret'];?>">
            </p>
            <p>
                <label for="number_of_tweets"><?php _e('Number of tweets', 'eztweet');?></label>
                <input type="text" name="inputs[number_of_tweets]" class="regular-text" id="number_of_tweets" value="<?php if(isset($inputs['number_of_tweets'])) echo $inputs['number_of_tweets'];?>" placeholder="<?php _e('Default will be 1 tweet','eztweet');?>">
            </p>
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'eztweet')?>" />
            </p>
        </form>

        <h3><?php _e('Shorcodes you may use', 'eztweet'); ?></h3>
        <p>[tweet_display] : <?php _e('Returns one simple last tweet you may enter whatever you want to show', 'eztweet'); ?></p>
        <p>class eztweet_plugin->array_of_tweets <?php _e('will return an array of the last number of tweets you have set', 'eztweet'); ?></p>
        <p><?php _e('You can use the Ez Twitter Widget to display the Tweets and choose the number of them', 'eztweet'); ?></p>


</div>
