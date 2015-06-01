<?php
namespace Boxalino\Landingpage;

/**
 * Boxalino Landingpage Proxy
 *
 * Provides a cURL based wrapper to request the content of a Boxalino
 * landing page and return its content as a string
 */
class Proxy
{
	// the cookies starting with "cem" contain the boxalino tracking
	protected $cookiePrefix = 'cem';

	// don't forward these headers
	protected $hideHeaders = array(
		'authenticate',
		'connection',
		'content-encoding',
		'cookie',
		'keep-alive',
		'proxy-authenticate',
		'proxy-authorization',
		'proxy-connection',
		'set-cookie',
		'set-cookie2',
		'te',
		'trailer',
		'transfer-encoding',
		'upgrade',
		'www-authenticate',
		'x-powered-by'
	);

	/**
	 * fetch the landing page and return it
	 *
	 * @param $url URL of the landing page including protocol & path, i.e. https://xyz.per-intelligence.com/example
	 * @param $parameters optional, array of GET parameters to send along
	 * @param $returnTransfer optional, set to FALSE if you want to return the content directly to stdout
	 * @return contenct as a string or cURL state if $returnTransfer is set to FALSE
	 */
	public function getContent($url, $parameters = array(), $returnTransfer = TRUE)
    {
        $encodedParameters = array();
		if (is_array($parameters)) {
			foreach($parameters as $k => $v) {
				$encodedParameters[] = rawurlencode($k) . '=' . rawurlencode($v);
			}
		}
		$finalUrl = is_array($encodedParameters) ? $url . '?' . implode('&', $encodedParameters) : $url;

        $curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_HTTPHEADER => $this->_extractHeader(parse_url($url, PHP_URL_HOST)),
				CURLOPT_COOKIE => $this->_extractCookie(),
				CURLOPT_URL => $finalUrl,
				CURLOPT_RETURNTRANSFER => $returnTransfer,
				CURLOPT_CONNECTTIMEOUT => 5, // connection timeout
				CURLOPT_TIMEOUT => 5, // request timeout
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_ENCODING => 'identity',
				CURLOPT_HEADER => FALSE, // don't return the headers in the output
				CURLOPT_HEADERFUNCTION => array($this, '_parseHeader'), // function to parse the returned headers
			)
		);
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
    }

    /**
	 * get current headers to forward to the server
	 *
	 * @param $host Host name to request, i.e. xyz.per-intelligence.com
     * @return array headers to forward
     */
    protected function _extractHeader($host)
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : $this->_extractServerHeaders();
		$headers['Host'] = $host;
        $finalHeaders = array();

        foreach ($headers as $k => $v) {
            if (!in_array(strtolower($k), $this->hideHeaders)) {
                $finalHeaders[] = "{$k}: {$v}";
            }
        }

        return $finalHeaders;
    }

    /**
	 * get currently set HTTP headers
	 *
     * @return array currently set headers
     */
    protected function _extractServerHeaders()
    {
        $headers = array();
		$pattern = '/^HTTP_/';
		foreach($_SERVER as $key => $val) {
			if (preg_match($pattern, $key) ) {
				$headerKey = preg_replace($pattern, '', $key);
				$matches = explode('_', strtolower($headerKey));
				if (count($matches) > 0 && strlen($headerKey) > 2 ) {
					foreach($matches as $matchKey => $matchVal) $matches[$matchKey] = ucfirst($matchVal);
					$headerKey = implode('-', $matches);
				}
				$headers[$headerKey] = $val;
			}
		}
		return $headers;
    }

    /**
	 * get boxalino tracking cookies to forward to the landing page
	 *
     * @return string cookie string
     */
    protected function _extractCookie()
    {
        $finalCookies = array();

		if (isset($_COOKIES)) {
		    foreach ($_COOKIES as $k => $v) {
		        if (strpos($k, $this->cookiePrefix) === 0) {
		            $finalCookies[] = urlencode($k) . '=' . urlencode($v);
		        }
		    }
		}

        return implode('; ', $finalCookies);
    }

	/**
	 * called by cURL to parse http header
	 * sets cookies returned by the landing page
	 *
	 * @param $h cURL handle
	 * @param $data header line
	 * @return line size
	 */
	protected function _parseHeader($curl, $data) {
		$index = strpos($data, ':');
		if ($index > 0) {
			$key = strtolower(trim(substr($data, 0, $index)));
			if(strpos($key, 'set-cookie') === 0) {
				$parts = explode(';', trim(substr($data, $index + 1)));
				if (count($parts) > 0) {
					$value = explode('=', $parts[0]);
					$cookie = array('expires' => '');
					for ($i = 1; $i < count($parts); $i++) {
						$parameter = explode('=', $parts[$i]);
						$cookie[trim($parameter[0])] = isset($parameter[1]) ? trim($parameter[1]) : '';
					}
					setcookie(urldecode(trim($value[0])), urldecode(trim($value[1])), strtotime($cookie['expires']));
				}
			}
		}
		return strlen($data);
	}
}
