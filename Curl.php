<?php

namespace clean;
/**
 * Curl class to correctly facilitate common use cases and parallel requests.
 *
 * @author Adam Jon Richardson
 */
class Curl {
    /**
     * Make single curl requests.
     * 
     * @param \clean\CurlRequest $request
     * @return \clean\CurlResponse
     */
    public static function request(CurlRequest $request): CurlResponse {
        // init response object
        $response = new CurlResponse();
        // prepare and save handle
        $ch = self::prepareCurlHandle($request, $response); 
        // execute and store response body
        self::storeResponseBody($response, curl_exec($ch));
        // store meta info
        self::storeMetaInfo($ch, $request, $response);
        // close handle
        curl_close($ch);
        // return response
        return $response;
    }
    /**
     * Make curl requests in parallel.
     * 
     * @param type $requests
     * @return array
     * @throws CurlException
     */
    public static function requestParallel(CurlRequest ...$requests): array {
        // init curl multi handle
        $mh = curl_multi_init();
        // ensure $mh success
        if (!$mh) {
            throw new CurlException('curl_multi_init() returned false', CurlException::CODE_FAILED_MULTI_INIT);
        }
        // create running flag
        $running = null;
        // handles array
        $chs = [];
        // responses array
        $responses = [];
        // cycle through requests and set up
        foreach ($requests as $key => $request) {
            // save response
            $responses[$key] = new CurlResponse();
            // save handle to handles array
            $chs[$key] = self::prepareCurlHandle($request, $responses[$key]);
            // add handle to multi handle
            $multiAddSuccess = curl_multi_add_handle($mh, $chs[$key]);
            // ensure add handle
            if ($multiAddSuccess !== 0) {
                throw new CurlException('curl_multi_add_handle() returned error code ' . $multiAddSuccess, CurlException::CODE_FAILED_MULTI_INIT);
            }
        }
        // execute and continue to execute until all requests finshed running
        do {
            // execute curl requests
            curl_multi_exec($mh, $running);
            // block to avoid needless cycling until change in status
            $multiSelectSuccess = curl_multi_select($mh);
            // ensure multi select success
            if ($multiSelectSuccess === -1) {
                throw new CurlException('curl_multi_select() returned -1', CurlException::CODE_FAILED_MULTI_INIT);
            }
        } while($running > 0);
        // cycle through handles
        foreach ($chs as $key => $ch) {
            $response = $responses[$key];
            // execute and store response
            self::storeResponseBody($response, curl_multi_getcontent($ch));
            // store error data
            self::storeMetaInfo($ch, $request, $response);
            // close individual handle
            curl_multi_remove_handle($mh, $ch);
        }
        // return responses array
        return $responses;
    }
    
    private static function prepareCurlHandle(CurlRequest $request, CurlResponse $response) {
        // init individual curl handle
        $ch = curl_init();
        // ensure $ch success
        if (!$ch) {
            throw new CurlException("curl_init() returned false", CurlException::CODE_FAILED_INIT);
        }
        // set options
        $setoptArraySuccess = curl_setopt_array($ch, $request->getRawOptions());
        // ensure setopt array success
        if (!$setoptArraySuccess) {
            throw new CurlException("curl_setopt_array() returned false", CurlException::CODE_FAILED_SETOPT_ARRAY);
        }
        // conditionally set header function
        if ($request->getStoreResponseHeaders()) {
            $setoptSuccess = curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($headerLine) use ($response) {
                $response->addHeaderLine($headerLine);
            });
            // ensure setopt success
            if (!$setoptSuccess) {
                throw new CurlException("curl_setopt() returned false", CurlException::CODE_FAILED_SETOPT);
            }
        }
        // return handle
        return $ch;
    }
    
    private static function storeResponseBody(CurlResponse $response, $body) {
        // on failure , leave body at default '' value
        if ($body === false) {
            return;
        }
        // otherwise, store body
        $response->storeBody($body);
    }
    
    private static function storeMetaInfo($ch, CurlRequest $request, CurlResponse $response) {
        // retrieve response code
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // conditionally retrieve curl request info
        $info = ($request->getStoreInfo()) ? curl_getinfo($ch) : [];
        // store in response object
        $response->storeInfo($responseCode, $info);
        // retrieve error info
        $errorMessage = curl_error($ch);
        $errorNumber = curl_errno($ch);
        // store in response object
        $response->storeErrorData($errorNumber, $errorMessage);
    }
}