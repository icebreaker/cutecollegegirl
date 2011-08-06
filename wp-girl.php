<?php
/*	
	Fetches the `Cute college girl of the day` from `collegeHumor.com` and 
	creates a new post in WordPress.

	See README for more information.
*/
class CuteCollegeGirl
{
	public $info;

	function CuteCollegeGirl()
	{
		$this->clear();
	}

	public function fetch()
	{
		require_once('simple_html_dom.php');

		$url = 'http://www.collegehumor.com';

		$html = file_get_html($url.'/cutecollegegirl/');

		$node = $html->find('div[class=shareable cfx]', 0);
		$info = $node->find('div[id=girl_info]', 0);
		$bio = $node->find('div[id=bio]', 0);
		$link = $bio->find('a', 0);

		$results_one = '';
		$pattern = '/^(.*?) from (.*?)$/';
		preg_match_all($pattern, $info->children(0)->plaintext, $results_one);

		$results_two = '';
		$pattern = '/^School: (.*?) Year: (.*?) Major: (.*?)$/';
		preg_match_all($pattern, $info->children(1)->plaintext, $results_two);

		$this->info->name = $results_one[1][0];
		$this->info->hometown = ucwords($results_one[2][0]);
		$this->info->school = $results_two[1][0];
		$this->info->year = $results_two[2][0];
		$this->info->major = ucwords($results_two[3][0]);
		$this->info->link = $url . $link->getAttribute('href');
		$this->info->image = $link->children(0)->getAttribute('src');

		return true;
	}

	public function post($user_id=1)
	{
		if(!isset($_SERVER['HTTP_HOST']) || !isset($this->info->name))
			return false;

		$host = 'http://'.$_SERVER['HTTP_HOST'].'/';
		$path = 'wp-content/uploads/cutecollegegirl/';
		$filename = $path . str_replace(' ', '-', strtolower($this->info->name)) . '_' . md5(time()) . '.jpg';

		file_put_contents($filename, file_get_contents($this->info->image));

		$content = '<h3>' . $this->info->name .'</h3>';
		$content.= '<p><b>Year:</b> ' . $this->info->year. '<br/>';
		$content.= '<b>School:</b> ' . $this->info->school . '<br/>';
		$content.= '<b>Hometown:</b> ' . $this->info->hometown . '<br/>';
		$content.= '<b>Major:</b> ' . $this->info->major . '</p>';
		$content.= '<p><a href="' . $this->info->link . '" target="_blank"><img src="' . $host . $filename . '" alt="' . $this->info->name . '"/></a></p>';
		$content.= '<p>Click <a href="' . $this->info->link . '" target="_blank">here</a> for more.</p>';

		$post = array();
		$post['post_title'] = 'Cute college girl of the day - ' . $this->info->name;
		$post['post_content'] = $content;
		$post['post_status'] = 'publish';
		$post['post_author'] = $user_id;

		require_once('wp_dom.php');
		require_once('wp-blog-header.php');

		$post_id = wp_insert_post($post);

		if($post_id)
		{
			wp_add_post_tags($post_id, 'cutecollegegirl, collegehumor, '. $this->info->name);
		}

		return $post_id;
	}

	public function clear()
	{
		unset($this->info);
		$this->info = (object) array();
	}
}

$girl = new CuteCollegeGirl();

if($girl->fetch())
{
	$girl->post();
	$girl->clear();
}

unset($girl);
?>
