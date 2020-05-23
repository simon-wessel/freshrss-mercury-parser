<?php

class MercuryParserExtension extends Minz_Extension {

    const DEFAULT_MERCURY_API_URL = "http://mercury:3000/";

    public function init() {
        // Get configuration
        if (is_null(FreshRSS_Context::$user_conf->mercury_api_url)) {
            FreshRSS_Context::$user_conf->mercury_api_url = self::DEFAULT_MERCURY_API_URL;
            FreshRSS_Context::$user_conf->save();
        }

        // Manipulate entry before persisting it
        $this->registerHook('entry_before_insert', array($this, 'replaceEntry'));
    }

    public function replaceEntry($entry) {
        try {
            // Build request URL
            $parsedUrl = parse_url(FreshRSS_Context::$user_conf->mercury_api_url);
            if (!$parsedUrl) {
                Minz_Log::warning(sprintf("Couldn't parse Mercury Parser url"), ADMIN_LOG);
                return $entry;
            }

            $mercuryApiUrl = sprintf('%s://%s:%s', $parsedUrl['scheme'], $parsedUrl['host'], $parsedUrl['port']);
            $apiRequestUrl = sprintf("%s/parser?url=%s", $mercuryApiUrl, urlencode($entry->link()));

            // Request data
            $jsonResponse = file_get_contents($apiRequestUrl);
            $mercuryData = json_decode($jsonResponse, true);

            if (!array_key_exists('content', $mercuryData)) {
                Minz_Log::warning(sprintf("Received unexpected response from Mercury Parser: %s", $jsonResponse), ADMIN_LOG);
                return $entry;
            }

            // Replace the article content
            $entry->_content($mercuryData['content']);

            // Make sure the cover image is being used in the article
            if (array_key_exists('lead_image_url', $mercuryData) && !empty($mercuryData['lead_image_url'])) {
                $leadImageUrl = $mercuryData['lead_image_url'];

                if (!empty(basename($leadImageUrl)) && strpos($entry->content(), basename($leadImageUrl)) === false) {
                    $entry->_content(sprintf("<img src=\"%s\">", $leadImageUrl) . "\n" . $entry->content());
                }
            }
        } catch (Exception $e) {
            Minz_Log::warning($e->getMessage(), ADMIN_LOG);
        }

        return $entry;
    }


    public function handleConfigureAction() {
        $this->registerTranslates();

        if (Minz_Request::isPost()) {
            FreshRSS_Context::$user_conf->mercury_api_url = Minz_Request::param('mercury-api-url', self::DEFAULT_MERCURY_API_URL);
            FreshRSS_Context::$user_conf->save();
        }
    }

}
