<?php

class MercuryParserExtension extends Minz_Extension {

    // Mercury Parser API endpoint (ToDo: make this configurable)
    const MERCURY_API_URL = "http://mercury:3000/parser?url=%s";

    public function init() {
        // Manipulate entry before persisting it
        $this->registerHook('entry_before_insert', array($this, 'replaceEntry'));
    }

    public function replaceEntry($entry) {
        try {
            // Get data from Mercury Parser
            $jsonResponse = file_get_contents(sprintf(static::MERCURY_API_URL, $entry->link()));

            $mercuryData = json_decode($jsonResponse, true);

            if (!array_key_exists('content', $mercuryData)) {
                Minz_Log::warning(sprintf("Received unexpected response from Mercury Parser: %s", $jsonResponse), ADMIN_LOG);
                return $entry;
            }

            // Replace the article content
            $entry->_content($mercuryData['content']);

            // Make sure the cover image is being used in the article
            if (array_key_exists('lead_image_url', $mercuryData)) {
                $leadImageUrl = $mercuryData['lead_image_url'];

                if (strpos($entry->content(), basename($leadImageUrl)) === false) {
                    $entry->_content(sprintf("<img src=\"%s\">", $leadImageUrl) . "\n" . $entry->content());
                }
            }
        } catch (Exception $e) {
            Minz_Log::warning($e->getMessage(), ADMIN_LOG);
        }

        return $entry;
    }

}
