<?php

class DemocracyNowBridge extends BridgeAbstract
{
    const MAINTAINER = 'SeanOMik';
    const NAME = 'Democracy Now!';
    const URI = 'https://www.democracynow.org';
    const DESCRIPTION = 'Returns newest articles by topic';
    const PARAMETERS = [
        "Topic Name" => [
            'topic' => [
                'name' => 'Topic Name',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'Gaza'
            ]
        ]
    ];

    public function collectData()
    {
        $url = self::URI . '/topics/' . strtolower($this->getInput('topic'));
        $html = getSimpleHTMLDOM($url);

        // Loop on each blog post entry
        foreach ($html->find('div.news_item') as $element) {
            $titleElement = $element->find('div.content > *:nth-child(2) > a', 0);
            $headline = $titleElement->innertext;
            $articleUri = self::URI . $titleElement->href;

            $articleHtml = getSimpleHTMLDOMCached($articleUri);
            
            $articleDateStr = $articleHtml->find(".date", 0)->innertext;
            $articleTimestamp = strtotime($articleDateStr);

            $articleContent = $articleHtml->find("div#headlines > article > div.headline_body > div.headline_summary", 0);

            if (is_null($articleContent)) {
                // if articleContent is null, it may be a transcript
                $articleContent = $articleHtml->find("div#story_text > div#transcript > div.text", 0);
            }

            $articleText = "";
            if (!is_null($articleContent)) {
                $articleText = $articleContent->save();
            }

            // get the thumbnail uri and get the higher quality uri
            $thumbnailUri = ($element->find('.media.image > img', 0))->getAttribute("data-src");
            $thumbnailUri = str_replace("w320", "quarter_hd", $thumbnailUri);

            // Fill item
            $item = [];
            $item['uri'] = $articleUri;
            $item['title'] = $headline;
            $item['timestamp'] = $articleTimestamp;
            $item['author'] = 'Unknown';
            $item['content'] = $articleText;
            $item['uid'] = $item['uri'];
            $item['enclosures'] = [
                $thumbnailUri
            ];

            $this->items[] = $item;
        }
    }
}
