<?php

namespace Klkvsk\DtoGenerator\Schema;

enum EnumValues
{
    case ZERO;
    case ONE;
    case KEYS;
    case CONST;
    case AUTO;

    public function process(array $array): array
    {
        $mode = $this;
        if ($mode == self::AUTO) {
            if (function_exists('array_is_list') && array_is_list($array)) {
                $mode = self::CONST;
            } else {
                $mode = self::KEYS;
            }
        }

        return match ($mode) {
            self::ZERO  => array_values($array),
            self::ONE   => array_combine(
                range(1, count($array)),
                $array,
            ),
            self::CONST => array_combine(
                $array,
                array_map(
                    function ($key) {
                        $key = preg_replace_callback(
                            '/([a-z])([A-Z])/',
                            fn ($m) => strtoupper($m[1]) . '_' . $m[2],
                            $key
                        );
                        $key = preg_replace('/[^a-zA-Z0-9]/', '_', $key);
                        $key = strtoupper($key);
                        return $key;
                    },
                    $array
                )
            ),
            default => $array
        };
    }
}