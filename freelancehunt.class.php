<?php

namespace Mike4ip;

/**
 * Class Freelancehunt
 * @package Mike4ip
 */
class Freelancehunt
{
    /**
     * ID or login
     * @see https://freelancehunt.com/my/api
     * @var string
     */
    protected $api_token;

    /**
     * Account secret key
     * @see https://freelancehunt.com/my/api
     * @var string
     */
    protected $api_secret;

    /**
     * API endpoint url
     * @var string
     */
    protected $api_url = 'https://api.freelancehunt.com';

    /**
     * Freelancehunt constructor.
     * @param string $api_token
     * @param string $api_secret
     */
    public function __construct(string $api_token, string $api_secret)
    {
        $this->api_token = $api_token;
        $this->api_secret = $api_secret;
    }

    /**
     * @param int|null $page
     * @param int|null $per_page
     * @param string|null $filter
     * @return array
     */
    public function getThreads(int $page = null, int $per_page = null, string $filter = null): array
    {
        return $this->query('threads', [
            'filter' => $filter,
            'page' => $page,
            'per_page' => $per_page
        ]);
    }

    /**
     * @param int $thread
     * @return array
     */
    public function getThreadMessages(int $thread): array
    {
        return $this->query('threads/'.$thread);
    }

    /**
     * @param int $thread
     * @param string $text
     * @return array
     */
    public function sendMessage(int $thread, string $text): array
    {
        return $this->query('threads/'.$thread, ['message' => $text], 'POST');
    }

    /**
     * @return array
     */
    public function getFeed(): array
    {
        return $this->query('my/feed');
    }

    /**
     * @param string $login
     * @param array|null $include
     * @return array
     */
    public function getProfile(string $login = 'me', array $include = null): array
    {
        return $this->query('profiles/'.$login, ['include' => is_array($include) ? implode(',', $include) : null]);
    }

    /**
     * @return array
     */
    public function getAvailableSkills(): array
    {
        return $this->query('skills');
    }

    /**
     * @param int|null $page
     * @param int|null $per_page
     * @param array|null $skills
     * @param array|null $tags
     * @return array
     */
    public function getProjectsList(int $page = null, int $per_page = null, array $skills = null, array $tags = null): array
    {
        $skills = is_array($skills) ? implode(',', $skills) : null;
        $tags = is_array($tags) ? implode(',', $tags) : null;

        return $this->query('projects', [
            'page' => $page,
            'per_page' => $per_page,
            'skills' => $skills,
            'tags' => $tags
        ]);
    }

    /**
     * @param int $project_id
     * @return array
     */
    public function getProjectInfo(int $project_id): array
    {
        return $this->query('projects/'.$project_id);
    }

    /**
     * @param int $project_id
     * @return array
     */
    public function getProjectBids(int $project_id): array
    {
        return $this->query('projects/'.$project_id.'/bids');
    }

    /**
     * @param int $project_id
     * @param string $comment
     * @param int $days_to_deliver
     * @param string|null $safe_type
     * @return array
     */
    public function createBid(int $project_id, string $comment, int $days_to_deliver = 1, string $safe_type = null): array
    {
        return $this->query('projects/'.$project_id, [
            'days_to_deliver' => $days_to_deliver,
            'safe_type' => $safe_type,
            'comment' => $comment
        ], 'POST');
    }

    /**
     * @param $api_secret string ваш секретный ключ
     * @param $url string URL вызова API вместе в GET параметрами (если есть), например, https://api.freelancehunt.com/threads?filter=new
     * @param $method string метод запроса, например GET, POST, PATCH
     * @param string $post_params string
     * @return string подпись
     */
    public function sign($api_secret, $url, $method, $post_params = '') {
        return base64_encode(hash_hmac("sha256", $url.$method.$post_params, $api_secret, true));
    }

    /**
     * @param string $method
     * @param array $params
     * @param string $type
     * @return array
     */
    public function query(string $method, array $params = [], string $type = "GET"): array
	{
	    $url = $this->api_url . '/' . $method;
	    $add = [];

	    if($type == "GET") {
            foreach ($params as $key => $val)
                if (!is_null($val))
                    $add[$key] = $val;

            if (count($add))
                $url .= '?' . http_build_query($add);
        }

		$curl = curl_init();

		if($type == "GET") {
            $signature = $this->sign($this->api_secret, $url, $type);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_USERPWD => $this->api_token . ":" . $signature,
                CURLOPT_URL => $url
            ]);
        } else {
            $params = http_build_query($params);
            $signature = $this->sign($this->api_secret, $url, $type, $params);

            curl_setopt_array($curl, [
                CURLOPT_USERPWD => $this->api_token . ":" . $signature,
                CURLOPT_URL => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $params,
            ]);
        }

		$return = json_decode(curl_exec($curl), true);
		curl_close($curl);
		return $return;
	}
}