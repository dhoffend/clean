<?php

namespace clean;
/**
 * Description of CurlRequest
 *
 * @author Adam Jon Richardson
 */
class CurlRequest {
    private $url;
    private $getVars;
    private $postVars;
    private $method;
    private $headers;
    private $storeResponseHeaders;
    private $storeInfo;
    private $options;
    
    function __construct() {
        $this->options = [
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false
        ];
        $this->method = 'get';
        $this->cookieFile = false;
        $this->getVars = [];
        $this->postVars = [];
        $this->headers = [];
        $this->storeInfo = false;
        $this->storeResponseHeaders = false;
    }
    
    public function withMethod(string $method): CurlRequest {
        switch ($method) {
            case 'get':
                break;
            case 'post':
                $this->options[CURLOPT_POST] = true;
                break;
            case 'delete':
                $this->options[CURLOPT_CUSTOMREQUEST] = "DELETE";
                break;
            case 'put':
                $this->options[CURLOPT_CUSTOMREQUEST] = "PUT";
                break;
            case 'head':
                $this->options[CURLOPT_CUSTOMREQUEST] = "HEAD";
                break;
            default:
                throw new CurlException('Invalid method argument passed to CurlRequest->withMethod() method. Must be "get", "post", "delete", "put", or "head".', CurlException::CODE_FAILED_INVALID_HTTP_METHOD);
        }
        
        $this->method = $method;
        return $this;
    }
    
    public function withUrl(string $url): CurlRequest {
        // store url in instance var so get variables can be automatically encoded and appended if added before or after url
        $this->url = $url;
        return $this;
    }
    
    public function withGetVars(array $vars): CurlRequest {
        // store get vars so they can be automatically encoded and appended if added before or after url
        $this->getVars = $vars;
        return $this;
    }

    public function withPostVars(array $vars): CurlRequest {
        // automatically change method to post if currently get
        if ($this->method === 'get') {
            $this->method = 'post';
            $curlopts[CURLOPT_POST] = true;
        }
        // add post fields and return
        $curlopts[CURLOPT_POSTFIELDS] = $vars;
        return $this;
    }

    public function withCookieFile(string $file): CurlRequest {
        // set curlopts cookie file path
        $curlopts[CURLOPT_COOKIEJAR] = $file;
        $curlopts[CURLOPT_COOKIEFILE] = $file;
        return $this;
    }

    public function withHeaders(array $headers): CurlRequest {
        $this->options[CURLOPT_HTTPHEADER] = $headers;
        return $this;
    }

    public function withAuthDigest(string $username, string $password): CurlRequest {
        $this->options[CURLOPT_USERPWD] = $username . ':' . $password;
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
        return $this;
    }
    
    public function withAuthBasic(string $username, string $password): CurlRequest {
        $this->options[CURLOPT_USERPWD] = $username . ':' . $password;
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
        return $this;
    }
    
    public function withAuthNTLM(string $username, string $password): CurlRequest {
        $this->options[CURLOPT_USERPWD] = $username . ':' . $password;
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
        return $this;
    }
    
    public function withAuthAny(string $username, string $password): CurlRequest {
        $this->options[CURLOPT_USERPWD] = $username . ':' . $password;
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
        return $this;
    }
    
    public function withAuthAnySafe(string $username, string $password): CurlRequest {
        $this->options[CURLOPT_USERPWD] = $username . ':' . $password;
        $this->options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
        return $this;
    }
    
    public function withUserAgent(string $userAgent) {
        $this->options[CURLOPT_USERAGENT] = $userAgent;
        return $this;
    }

    public function withOptions(array $options): CurlRequest {
        $this->options = $options + $this->options;
        return $this;
    }
    
    public function withOption(int $option, $value) {
        $this->options[$option] = $value;
        return $this;
    }
    
    public function withConnectTimeout(int $seconds): CurlRequest {
        $this->options[CURLOPT_CONNECTTIMEOUT] = $seconds;
        return $this;
    }
    
    public function withTimeout(int $seconds): CurlRequest {
        $this->options[CURLOPT_TIMEOUT] = $seconds;
        return $this;
    }
    
    public function withRedirectsAllowed(int $maxRedirects) {
        if ($maxRedirects < 1) { 
            return;
        }
        $this->options[CURLOPT_FOLLOWLOCATION] = true;
        $this->options[CURLOPT_MAXREDIRS] = $maxRedirects;
    }
    
    public function withStoreResponseHeaders(bool $value): CurlRequest {
        $this->storeResponseHeaders = $value;
        return $this;
    }
    
    public function getStoreResponseHeaders(): bool {
        return $this->storeResponseHeaders;
    }
    
    public function getStoreInfo(): bool {
        return $this->storeInfo;
    }
    
    public function getOptions(): array {
        $curlopts = $this->options;
        // set curlopts url
        $curlopts[CURLOPT_URL] = $this->prepareUrl();
        return $curlopts;
    }
    
    private function prepareUrl(): string {
        // check if we can exit early without any get vars
        if (!$this->getVars) {
            return $this->url;
        }
        // store '?' position
        $questionMarkPos = strpos($this->url, '?');
        // check if '?' absent
        if ($questionMarkPos === false) {
            // append get vars with '?'
            return $this->url . '?' . http_build_query($this->getVars);
        }
        // check for '?' at very end
        if ($questionMarkPos !== (strlen($this->url) - 1)) {
            // append get vars without '?', as it is the last character in the url
            return $this->url . http_build_query($this->getVars);
        }
        // append get vars with '&', as the url already has a '?', and it's not the last character, so there are already get vars
        return $this->url . '&' . http_build_query($this->getVars);
    }
}
