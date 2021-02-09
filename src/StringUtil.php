<?php declare(strict_types=1);

namespace Sms77ShopwareApi;

class StringUtil {
    public static function startsWith(string $haystack, string $needle): bool {
        return mb_strpos($haystack, $needle) === 0;
    }

    public static function endsWith(string $haystack, string $needle): bool {
        $length = mb_strlen($needle);

        return $length ? mb_substr($haystack, -$length) === $needle : true;
    }

    public static function toGetter(string $fnPart): string {
        return 'get' . ucfirst($fnPart);
    }
}