<?php
//creamos la clase con el nombre del archivo
class eztweet_plugin{
	
	//en el constructor es donde llamamos a las acciones que vayamos creando
	public function __construct() {
		add_action('admin_menu',array($this,"add_option_menu"));
		add_action('wp_enqueue_scripts', array($this,"load_all_scripts"));
		add_shortcode('tweet_display', array($this, "print_the_tweet"));
		add_action('widgets_init', array($this, 'eztweet_create_widget'));
        add_action('plugins_loaded', array($this, 'eztweet_text'));
	}

	public function admin_page(){
		include('inc/admin_page.php');
	}

    public function eztweet_text() {
        load_plugin_textdomain('eztweet', false, basename(dirname(__FILE__)) . '/langs');
    }

	public function eztweet_create_widget() {
	include_once plugin_dir_path(__FILE__) . 'inc/widget.php';
	register_widget('eztweet_widget');
	}

	public function add_option_menu(){
		add_options_page("eztweet_plugin", "EZ Tweet Options", "read", __FILE__,array($this, 'admin_page'));
	}

	public function load_all_scripts(){
		wp_enqueue_style('vass-ss-stylesheet', plugin_dir_url( __FILE__ ) . 'css/style.css');
	}

	public function array_of_tweets(){
			// Hacemos los request
		    $inputs = $this->get_options_fromadmin();
		    $url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
		    $r = $this->buildAuthorizationHeader();
		    $header = array($r, 'Expect:');
		if(function_exists('curl_init')) {
		    	$options = array( CURLOPT_HTTPHEADER => $header,
		                      //CURLOPT_POSTFIELDS => $postfields,
		                      CURLOPT_HEADER => false,
		                      CURLOPT_URL => $url,
		                      CURLOPT_RETURNTRANSFER => true,
		                      CURLOPT_SSL_VERIFYPEER => false);

				$feed = curl_init();
				curl_setopt_array($feed, $options);
				$json = curl_exec($feed);
				curl_close($feed);

				$twitter_data = json_decode($json);

		//enviamos los tweets
		$i = (isset($inputs['number_of_tweets']) && $inputs['number_of_tweets'] > 1) ? $inputs['number_of_tweets'] : 1;
		return array_slice($twitter_data, 0, $i);
			} else {
				echo "You need to set up curl";
				return false;
			}
	}

	public function array_of_tweets_widget($i){
		// Hacemos los request
		$inputs = $this->get_options_fromadmin();
		$url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
		$r = $this->buildAuthorizationHeader();
		$header = array($r, 'Expect:');
		$options = array( CURLOPT_HTTPHEADER => $header,
			//CURLOPT_POSTFIELDS => $postfields,
			CURLOPT_HEADER => false,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false);

		$feed = curl_init();
		curl_setopt_array($feed, $options);
		$json = curl_exec($feed);
		curl_close($feed);

		$twitter_data = json_decode($json);

		//enviamos los tweets
		return array_slice($twitter_data, 0, $i);
	}

	public function print_the_tweet() {
		ob_start();
		$tweets = $this->array_of_tweets(); ?>
		<div class="tweetcontent oval-thought-border">
				<div class="tweet-text">
					<span class="dashicons dashicons-twitter tweet-dash"></span>
				 <?php
				 	if($tweets && is_array($tweets)) {
				 		echo "<a href=\"".$tweets[0]->entities->urls[0]->url."\" target=\"_blank\">".$tweets[0]->text."</a><br />";
						echo "<a href=\"http://twitter.com/".$tweets[0]->user->screen_name."\" target=\"_blank\">@".$tweets[0]->user->screen_name."</a><br />";
					} ?>
				</div>
		</div><?php
		$output_string=ob_get_contents();
		ob_end_clean();

		return $output_string;
	}

	public function print_the_tweets_forwidget($times) {
		$tweets = $this->array_of_tweets_widget($times);
		for($i=1;$i<=$times;$i++) { ?>
			<div class="tweetcontent">
			<div class="tweet-text">
				<span class="dashicons dashicons-twitter tweet-dash "></span>
				<?php
			if($tweets && is_array($tweets)) {
				echo "<a href=\"" . $tweets[$i - 1]->entities->urls[0]->url . "\" target=\"_blank\">" . $tweets[$i - 1]->text . "</a><br />";
				echo "<a href=\"http://twitter.com/" . $tweets[$i - 1]->user->screen_name . "\" target=\"_blank\">@" . $tweets[$i - 1]->user->screen_name . "</a><br />";
			} ?>
			</div>
			</div><?php
		}
	}

    private function buildBaseString($baseURI, $method, $params) {
        $r = array();
        ksort($params);
        foreach($params as $key=>$value){
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method."&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }

    private function buildAuthorizationHeader() {
    	$oauth = $this->data_tweet();
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach($oauth as $key=>$value)
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        $r .= implode(', ', $values);
        return $r;
    }

    private function data_tweet() {
	    $url = "https://api.twitter.com/1.1/statuses/user_timeline.json";
	    $inputs = $this->get_options_fromadmin();

	    $oauth_access_token = $inputs['oauth_access_token'];
	    $oauth_access_token_secret = $inputs['oauth_access_token_secret'];
	    $consumer_key = $inputs['consumer_key'];
	    $consumer_secret = $inputs['consumer_secret'];

	    $oauth = array( 'oauth_consumer_key' => $consumer_key,
	                    'oauth_nonce' => time(),
	                    'oauth_signature_method' => 'HMAC-SHA1',
	                    'oauth_token' => $oauth_access_token,
	                    'oauth_timestamp' => time(),
	                    'oauth_version' => '1.0');

	    $base_info = $this->buildBaseString($url, 'GET', $oauth);
	    $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
	    $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
	    $oauth['oauth_signature'] = $oauth_signature;

	    return $oauth;
	}

	private function get_options_fromadmin() {
		$option_name = 'ez_tweet_inputs';
	    $inputs = get_option( $option_name );
	    return $inputs;
	}
}