<?php

namespace Keystone\Toolkit\Helpers\VirtualDom;

use Keystone\Toolkit\Helpers\VirtualDom\Element;

use Illuminate\Support\Arr;

class Render
{

    static function attributes($vattrs = [])
    {
        if (empty($vattrs)) return [];
        $attrs = [];
        foreach ($vattrs as $attr => $value) {
            if ($value !== false)
                $attrs[] = $attr . '="' . $value . '"';
        }

        return join(' ', $attrs);
    }

    static function htmlPart($vdom, $part = null, $tabSpace = 0)
    {
        if (empty($vdom)) return '';
        if (is_string($vdom)) return $vdom;

        $tabStart = str_repeat("    ", $tabSpace);

        if (!is_object($vdom)) dd($vdom);
        //  dd($vdom->attributes);
        $attributes = $vdom->attributes ? self::attributes(is_array($vdom) ? $vdom['attributes'] :  $vdom->attributes) : [];

        switch ($part) {
            case 'open':
                $open =  $tabStart . "<{$vdom->tag} {$attributes} >";
                return $open;
                break;
            case 'content':
                $content = '';
                if ($vdom->children && count($vdom->children) > 0) {
                    foreach ($vdom->children as $child) {
                        if (!empty($child)) {
                            $content .= self::html($child, $tabSpace + 1);
                        }
                    }
                }
                return $content;
                break;
            case 'close':
                $close =  $tabStart . "</{$vdom->tag}>";
                return $close;
                break;
            default:
                return Render::html($vdom);
        }
    }

    static function html($vdom, $tabSpace = 0)
    {

        if (empty($vdom)) {
            return '';
        } elseif (is_string($vdom)) {
            return $vdom;
        } elseif (is_array($vdom) && Arr::isAssoc($vdom)) {
            if (!isset($vdom['tag'])) {
                throw new \InvalidArgumentException('Cannot render an VirtualNodes no tag is set');
            }
            $vdom = new Element($vdom['tag'], $vdom['attributes'] ?? [], $vdom['children'] ?? []);
        }

        if (is_array($vdom) && Arr::isList($vdom)) {
            //$vdom var is array of...
            $resp = [];
            foreach ($vdom as $vd) {
                if (is_null($vd)) {
                    throw new \InvalidArgumentException('Cannot render an array of VirtualNodes, one of the items is null.');
                }
                $resp[] = Render::html($vd);
            }
            return join("\n", $resp);
        }

        if (is_string($vdom) || is_numeric($vdom)) return $vdom;

        if (!is_object($vdom) && is_array($vdom)) $vdom = (object) $vdom;

        if (is_null($vdom->tag)) {
            dd($vdom);
            throw new \InvalidArgumentException('Cannot render a VirtualNode without a tag.');
        }

        $tabStart = str_repeat("    ", $tabSpace);

        $attributes = self::attributes($vdom->attributes ?? []);
        $close_tag = true;
        $break = false;

        $open = $tabStart . "<{$vdom->tag} {$attributes} ";

        if (in_array($vdom->tag, ['input', 'option']) && count($vdom->children ?? []) == 0) $close_tag = false;

        $open .= $close_tag ? ">" :  "/>";
        if (is_numeric($vdom->children)) $vdom->children = [(string) $vdom->children];
        $content = '';
        if (is_string($vdom->children ?? '')) {
            $content .= $vdom->children;
        } elseif ($vdom->children && count($vdom->children) > 0) {
            $is_text = true;
            foreach ($vdom->children as $child) {
                if (!empty($child)) {
                    if (!is_string($child)) $is_text = false;
                    $content .= self::html($child, $tabSpace + 1);
                }
            }
            if ($is_text !== true && $content !== '') $break = true;
        }




        $open .= $break ? "\n" : "";

        $close =  $close_tag ? ($break ? $tabStart : "") . "</{$vdom->tag}> \n" : "";
        if (is_null($close)) {
            throw new \InvalidArgumentException('Cannot render a VirtualNode without a closing tag.');
        }

        return $open . $content . $close;
    }
}
