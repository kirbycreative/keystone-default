<?php

namespace Keystone\Toolkit\Helpers\VirtualDom;

use \DOMDocument;

use Keystone\Toolkit\Helpers\VirtualDom\Element;

class Parse
{

    static function element($dom)
    {
        $vdom = [];

        if ($dom->nodeType === XML_TEXT_NODE) {
            return $dom->data;
        } else {
            $vdom = new Element($dom->nodeName, $dom->attributes ?? [],);
            /*
            if($dom->nodeName){
               // $vdom['tag'] = $dom->nodeName;
            }*/
            $attrs = [];
            if ($dom->hasAttributes()) {
                foreach ($dom->attributes as $attr) {
                    $attrs[$attr->nodeName] = $attr->nodeValue;
                }
            }

            $vdom = new Element($dom->nodeName, $attrs,);

            if ($dom->childNodes) {
                foreach ($dom->childNodes as $item) {
                    if ($item->nodeType === XML_TEXT_NODE && empty(trim($item->textContent))) continue;
                    elseif ($item->nodeType === XML_TEXT_NODE) {
                        $vdom->addTextChild($item->data);
                    } else {
                        $vdom->addChild(Parse::element($item));
                    }
                }
            }

            return $vdom;
        }
    }

    static function html($html, $root = 'html')
    {


        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $vdom = [];

        if ($doc->childNodes) {

            foreach ($doc->childNodes as $item) {
                if (in_array($item->nodeName, ['xml']) || in_array($item->nodeType, [XML_DOCUMENT_TYPE_NODE, XML_PI_NODE])) continue;
                $vdom[] = Parse::element($item);
            }
        }

        $firstNode = $vdom[0];

        while (!empty($firstNode->children) && count($firstNode->children) == 1 &&  $firstNode->tag !== $root) {
            $firstNode = $firstNode->children[0];
        }

        if ($firstNode->tag === $root) $firstNode = $firstNode->children[0];

        $firstNode = $firstNode->children;
        //dd($firstNode);



        return $firstNode;
    }

    static function referenceTags($vdom) {}
}
