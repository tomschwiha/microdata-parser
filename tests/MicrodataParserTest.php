<?php

namespace YusufKandemir\MicrodataParser\Tests;

use YusufKandemir\MicrodataParser\MicrodataDOMDocument;
use YusufKandemir\MicrodataParser\MicrodataParser;

class MicrodataParserTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        libxml_use_internal_errors(true); // Ignore warnings of DOMDocument::loadHTML check
    }

    protected function getParser($data)
    {
        $dom = new MicrodataDOMDocument;
        $dom->loadHTML($data['source']);
        $dom->documentURI = $data['uri'];

        return new MicrodataParser($dom);
    }

    /**
     * @dataProvider data
     */
    public function testItConvertsMicrodataToObjectFormat($data)
    {
        $parser = $this->getParser($data);

        $result = $parser->toObject();

        $this->assertEquals(json_decode($data['result']), $result);
    }

    /**
     * @dataProvider data
     */
    public function testItConvertsMicrodataToArrayFormat($data)
    {
        $parser = $this->getParser($data);

        $result = $parser->toArray();

        $this->assertEquals(json_decode($data['result'], true), $result);
    }

    /**
     * @dataProvider data
     */
    public function testItConvertsMicrodataToJsonFormat($data)
    {
        $parser = $this->getParser($data);

        $result = $parser->toJSON();

        $this->assertJsonStringEqualsJsonString($data['result'], $result);
    }

    public function testItUsesAbsoluteUriHandlerWhenHandlingAbsoluteUris()
    {
        $baseUri = 'https://absolute.uri.handler/';
        $data = $this->data()['Itemref & src based tags'][0];
        $parser = $this->getParser($data);

        $resultBefore = $parser->toObject();
        $resultBeforeUri = $resultBefore->items[0]->properties->work[0];

        $this->assertNotContains($baseUri, $resultBeforeUri);

        $parser->setAbsoluteUriHandler(
            function (string $value, string $base) use ($baseUri) : string {
                return $baseUri . $value;
            }
        );

        $resultAfter = $parser->toObject();
        $resultAfterUri = $resultAfter->items[0]->properties->work[0];

        $this->assertContains($baseUri, $resultAfterUri);
    }

    /**
     * @todo Provide more test data
     */
    public function data()
    {
        return [
            // https://www.w3.org/TR/microdata/#ex-jsonconv
            'W3C Example' => [
                $this->getTestData('W3C', 'source.html', 'result.json')
            ],
            'Itemref & src based tags' => [
                $this->getTestData('Itemref', 'source.html', 'result.json')
            ],
            'Object & Data tags' => [
                $this->getTestData('Object & Data', 'source.html', 'result.json')
            ],
            'Itemid & Content attributes' => [
                $this->getTestData('Itemid & Content', 'source.html', 'result.json')
            ],
        ];
    }

    private function getTestData($folderName, $sourceName, $resultName)
    {
        $folderPath = __DIR__.'/data/'.$folderName.'/';

        $source = file_get_contents($folderPath . $sourceName);
        $result = file_get_contents($folderPath . $resultName);

        $uri = '';
        // Set $uri if URI specified in test data
        if (preg_match('/<!-- URI: (.*) -->/', $source, $matches)) {
            $uri = $matches[1];
        }

        return [
            'uri' => $uri,
            'source' => $source, // HTML String
            'result' => $result, // JSON String
        ];
    }
}
