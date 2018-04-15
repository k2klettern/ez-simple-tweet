<?php

use Abraham\TwitterOAuth\TwitterOAuth;

//creamos la clase con el nombre del archivo
class eztweet_plugin{

    private $status;
    private $imageurl;
    private $credentials;
    private $conection;
    private $content;
	//en el constructor es donde llamamos a las acciones que vayamos creando
	public function __construct() {

        $this->credentials = $this->get_options_fromadmin();
		if(isset($this->credentials['oauth_access_token'])) {
			$this->conection = new TwitterOAuth( $this->credentials['consumer_key'], $this->credentials['consumer_secret'], $this->credentials['oauth_access_token'], $this->credentials['oauth_access_token_secret'] );
		} else {
		    $this->conection = new TwitterOAuth($this->credentials['consumer_key'], $this->credentials['consumer_secret']);
        }

        if(!session_id()) session_start();

        $this->initHooks();
	}
	
	public function initHooks() {
	    if(isset($this->credentials['oauth_access_token'])) {
		    add_shortcode('tweet_display', array($this, "print_the_tweet"));
		    add_action('widgets_init', array($this, 'eztweet_create_widget'));
		    add_action( 'eztweet_hourly_event',  array($this, 'ezpost_hourly_tweet') );
	    }
		add_action('init', array($this, "ezt_redirect"));
		add_action('admin_menu',array($this,"add_option_menu"));
		add_action('wp_enqueue_scripts', array($this,"load_all_scripts"));
		add_action('plugins_loaded', array($this, 'eztweet_text'));
		add_action('init', array($this, 'actionLoginEztweet'));
		add_action('admin_init', array($this, 'ezTweetNow'));
    }

    public function ezt_activate() {
        add_option('ezt_do_activation_redirect', true);
    }

    
    public function ezt_redirect() {
        if (get_option('ezt_do_activation_redirect', false)) {
            delete_option('ezt_do_activation_redirect');
            register_uninstall_hook( __FILE__, array('eztweet_plugin', 'ezt_on_uninstall' ));
            //saving basic data
            $args = array(
                'consumer_key' => 'YQLZU0KMBicXDlwtQZwepnqaC',
                'consumer_secret' => '0K0uHmEU3hlVw59oTIzhGLeLpv6pzpqAY8arhCRphyOX8Upsz6',
                'number_of_tweets' => 1,
                'url_login' => home_url('?eztw=login'),
                'url_callback' => home_url('?eztw=callback'),
                'basic' => 1
            );
            if(!get_option('ez_tweet_inputs')) {
	            update_option( 'ez_tweet_inputs', $args );
            }
            if(!wp_get_schedule('eztweet_hourly_event')) {
	            wp_schedule_event( time(), 'hourly', 'eztweet_hourly_event' );
            }
        }
    }

    static function ezt_on_unistall() {
        delete_option('ez_tweet_inputs');
        wp_clear_scheduled_hook('eztweet_hourly_event');
        add_action('admin_notices', function() {
            echo "<div class=\"notice notice-success is-dismissible\">";
            echo "<p>";
            _e( 'Deleted!', 'eztweet' );
            echo "</p></div>";
        });
    }

    public function ezTweetNow() {
	    if(isset($_POST['tnow']) && $_POST['tnow'] == 'tnow') {
	        $this->ezpost_hourly_tweet();
        }
    }
    public function ezpost_hourly_tweet() {
	       $inputs = $this->get_options_fromadmin();
	       if(isset($inputs['activate_posttweets']) && $inputs['activate_posttweets'] == 1) {
	            $post = get_posts(array(
                    'numberposts' => 1,
                    'post_status' => 'publish',
                    'orderby' => 'rand',
                    'post_type' => 'post',
                ));

	            $this->status = $post[0]->post_title . " " . get_post_permalink($post[0]->ID) . ' #WordPress #Development #eztweet';
		        $post_thumbnail_id = get_post_thumbnail_id( $post[0]->ID );
	            $metadata = wp_get_attachment_metadata( $post_thumbnail_id );
	            $upload_dir = wp_get_upload_dir();
	            $this->imageurl = $upload_dir['basedir'] . "/" . $metadata['file'];
                $this->postTweet();
           } else {
	           wp_die(__('You have to activate post tweets in order to be availabel to send tweets', 'eztweet'), "", array('back_link' => true));
           }
    }

	public function admin_page(){
        if(!isset($this->credentials['oauth_access_token'])) {
	        echo "<a href=\"" . $this->buildTheButton() . "\">Conectar con Twitter</a>";
        } else {
	        echo $this->print_the_tweet();
        }
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
		$i = (isset($this->credentials['number_of_tweets'])) ? $this->credentials['number_of_tweets'] : 1;
		$statuses = $this->conection->get("statuses/user_timeline", ["count" => $i, "exclude_replies" => true, "include_entities" => true, 'tweet_mode'=>'extended', "result_type" => "mixed"]);

		return $statuses;
	}

	public function array_of_tweets_widget($i){
		$statuses = $this->conection->get("statuses/user_timeline", ["count" => $i, "exclude_replies" => true, "include_entities" => true, 'tweet_mode'=>'extended', "result_type" => "mixed"]);

		return $statuses;
	}

	public function print_the_tweet($atts) {
		ob_start();
		extract(shortcode_atts(
				array(
					'number' => '1',
                    'showimg' => false
				), $atts)
		);
		$tweets = $this->array_of_tweets_widget($number); ?>
        <div class="tweetcontent">
        <?php for($i=1;$i<=$number;$i++) { ?>
            <div class="tweet-text">
                <span class="dashicons dashicons-twitter tweet-dash "></span>
		        <?php
		        if ( $tweets && is_array( $tweets ) ) {
                    if(isset($tweets[$i - 1]->retweeted_status)) {
	                    echo "<a href=\"" . $tweets[ $i - 1 ]->retweeted_status->entities->media[0]->url . "\" target=\"_blank\">" . $tweets[ $i - 1 ]->full_text . "</a><br />";
	                    echo "<a href=\"http://twitter.com/" . $tweets[ $i - 1 ]->user->screen_name . "\" target=\"_blank\">@" . $tweets[ $i - 1 ]->user->screen_name . "</a><br />";
	                    if (isset($tweets[$i - 1]->retweeted_status->entities->media)) {
		                    echo "<ul>";
		                    foreach ($tweets[$i - 1]->retweeted_status->entities->media as $media) {
			                    echo "<li><img src='" . $media->media_url_https ."'></li>";
		                    }
		                    echo "</ul>";
	                    }
                    } else {
	                    echo "<a href=\"" . $tweets[ $i - 1 ]->entities->urls[0]->url . "\" target=\"_blank\">" . $tweets[ $i - 1 ]->full_text . "</a><br />";
	                    echo "<a href=\"http://twitter.com/" . $tweets[ $i - 1 ]->user->screen_name . "\" target=\"_blank\">@" . $tweets[ $i - 1 ]->user->screen_name . "</a><br />";
	                    if (isset($tweets[$i - 1]->entities->media)) {
		                    echo "<ul>";
		                    foreach ($tweets[$i - 1]->entities->media as $media) {
			                    echo "<li><img src='" . $media->media_url_https ."'></li>";
		                    }
		                    echo "</ul>";
	                    }
                    }



		        } ?>
            </div><?php
        } ?>
        </div>
        <?php
		$output_string=ob_get_contents();
		ob_end_clean();

		return $output_string;
	}

	public function print_the_tweets_forwidget($times) {
		$tweets = $this->array_of_tweets_widget($times);
		for($i=1;$i<=$times;$i++) { ?>
            <div class="tweetcontent">
			<?php for($i=1;$i<=$times;$i++) { ?>
                <div class="tweet-text">
                <span class="dashicons dashicons-twitter tweet-dash "></span>
				<?php
				if ( $tweets && is_array( $tweets ) ) {
					if(isset($tweets[$i - 1]->retweeted_status)) {
						echo "<a href=\"" . $tweets[ $i - 1 ]->retweeted_status->entities->media[0]->url . "\" target=\"_blank\">" . $tweets[ $i - 1 ]->full_text . "</a><br />";
						echo "<a href=\"http://twitter.com/" . $tweets[ $i - 1 ]->user->screen_name . "\" target=\"_blank\">@" . $tweets[ $i - 1 ]->user->screen_name . "</a><br />";
						if (isset($tweets[$i - 1]->retweeted_status->entities->media)) {
							echo "<ul>";
							foreach ($tweets[$i - 1]->retweeted_status->entities->media as $media) {
								echo "<li><img src='" . $media->media_url_https ."'></li>";
							}
							echo "</ul>";
						}
					} else {
						echo "<a href=\"" . $tweets[ $i - 1 ]->entities->urls[0]->url . "\" target=\"_blank\">" . $tweets[ $i - 1 ]->full_text . "</a><br />";
						echo "<a href=\"http://twitter.com/" . $tweets[ $i - 1 ]->user->screen_name . "\" target=\"_blank\">@" . $tweets[ $i - 1 ]->user->screen_name . "</a><br />";
						if (isset($tweets[$i - 1]->entities->media)) {
							echo "<ul>";
							foreach ($tweets[$i - 1]->entities->media as $media) {
								echo "<li><img src='" . $media->media_url_https ."'></li>";
							}
							echo "</ul>";
						}
					}



				} ?>
                </div><?php
			} ?>
            </div><?php
		}
	}


	private function get_options_fromadmin() {
		$option_name = 'ez_tweet_inputs';
	    $inputs = get_option( $option_name );
	    return $inputs;
	}

    public function getMediaId($settings) {

        $url = 'https://upload.twitter.com/1.1/media/upload.json';
        $method = 'POST';
        $twitter = new TwitterAPIExchange($settings);

        $file = file_get_contents($this->imageurl);
        $data = base64_encode($file);

        $params = array(
            'media_data' => $data
        );

        try {
            $data = $twitter->request($url, $method, $params);
        } catch (Exception $e) {
            echo 'Excepción capturada: ', $e->getMessage(), "\n";
            // hacer algo
            return null;
        }

        // para obtener más facilmente el media_id
        $obj = json_decode($data, true);

        // media_id en formato string
        return $obj ["media_id_string"];
    }

    /**
     * Sends Tweets
     */
    public function postTweet() {
        if($this->status) {
            $parameters['status'] = $this->status;
            if($this->imageurl) {
	            $media1                  = $this->conection->upload( 'media/upload', [ 'media' => $this->imageurl ] );
	            $parameters['media_ids'] = $media1->media_id_string;
	            add_action('wp_after_admin_bar_render', function() {
		            printf( '<div class="%1$s"><p>%2$s</p></div>', "notice notice-success", __('tweet sent', 'eztweet'));
	            });
            }
            try {
	            $this->conection->post( 'statuses/update', $parameters );
            } catch (Exception $e) {
	            wp_die('There was a problem performing this request post tweet eztweet', $e->getMessage());
            }
        }
    }

    public function getNewToken() {
        try {
		    $request_token = $this->conection->oauth(
			    'oauth/request_token', [
				    'oauth_callback' => $this->credentials['url_callback']
			    ]
		    );
	    } catch (Exception $e) {
		    wp_die('There was a problem performing this request eztweet', $e->getMessage());
	    }

	    $_SESSION['oauth_token'] = $request_token['oauth_token'];
	    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

	    $url = $this->conection->url(
		    'oauth/authorize', [
			    'oauth_token' => $request_token['oauth_token']
		    ]
	    );

	    return $url;
    }

    public function buildTheButton() {
	      $url = $this->getNewToken();
          return $url;
    }

    public function actionLoginEztweet() {
        ob_start();
        if(isset($_REQUEST['eztw'])) {
            if($_REQUEST['eztw'] == 'login') {

            }
            if($_REQUEST['eztw'] == 'callback') {
	            $oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier');

	            if (empty($oauth_verifier) ||
	                empty($_SESSION['oauth_token']) ||
	                empty($_SESSION['oauth_token_secret'])
	            ) {
		            // something's missing, go and login again
                    ob_start();
		            header('Location: ' . $this->getNewToken());
	            }

	            $connection = new TwitterOAuth(
		            $this->credentials['consumer_key'],
		            $this->credentials['consumer_secret'],
		            $_SESSION['oauth_token'],
		            $_SESSION['oauth_token_secret']
	            );

	            try {
	                $token = $connection->oauth(
			            'oauth/access_token', [
				            'oauth_verifier' => $oauth_verifier
			            ]
		            );
	            } catch (Exception $e) {
		            wp_die('Imposibel to get the tokens eztweet', $e->getMessage());
                }

	            $this->credentials['oauth_access_token'] = $token['oauth_token'];
	            $this->credentials['oauth_access_token_secret'] = $token['oauth_token_secret'];

	            update_option('ez_tweet_inputs', $this->credentials);
            }
        }
    }
}
