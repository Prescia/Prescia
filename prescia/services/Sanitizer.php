<?php

declare(strict_types=1);

namespace Prescia\Services;

final class Sanitizer
{
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function stripTags(string $value, bool $preserveLineBreaks = false): string
    {
        $result = strip_tags($value);
        if ($preserveLineBreaks) {
            $result = str_replace(["\r\n", "\r", "\n"], "<br/>\n", $result);
        }
        return $result;
    }

    public static function sanitizeHtml(string $value): string
    {
        $allowedTags = ['a', 'blockquote', 'br', 'code', 'em', 'li', 'ol', 'p', 'pre', 'strong', 'ul'];
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>' . $value . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $document->getElementsByTagName('div')->item(0);
        if ($root === null) return '';
        self::sanitizeHtmlNode($root, $allowedTags);
        $result = '';
        foreach ($root->childNodes as $child) $result .= $document->saveHTML($child);
        return $result;
    }

    /** @param list<string> $allowedTags */
    private static function sanitizeHtmlNode(\DOMNode $node, array $allowedTags): void
    {
        for ($child = $node->firstChild; $child !== null;) {
            $next = $child->nextSibling;
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, $allowedTags, true)) {
                    $node->replaceChild($child->ownerDocument->createTextNode($child->textContent), $child);
                } else {
                    for ($index = $child->attributes->length - 1; $index >= 0; $index--) {
                        $attribute = $child->attributes->item($index);
                        if ($attribute === null || ($tag !== 'a' && $attribute->name !== 'title') || ($tag === 'a' && !in_array($attribute->name, ['href', 'title'], true))) $child->removeAttributeNode($attribute);
                    }
                    if ($tag === 'a' && isset($child->attributes['href']) && !preg_match('/^(?:https?:|mailto:|\/|#)/i', $child->getAttribute('href'))) $child->removeAttribute('href');
                    self::sanitizeHtmlNode($child, $allowedTags);
                }
            }
            $child = $next;
        }
    }

    public static function text(mixed $value): string
    {
        if (is_array($value) || is_object($value) || is_resource($value)) {
            return '';
        }
        $text = (string) $value;
        return function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')
            ? ''
            : trim($text);
    }
}
