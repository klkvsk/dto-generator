<?php

namespace Klkvsk\DtoGenerator\Generator;

class CodeStyle
{
    const INDENTATION = '    ';

    public static function indent(string $code, int $baseLevel = 0): string
    {
        if (empty($code)) {
            return '';
        }

        $stack = new \SplStack();

        $bracedBlocks = [
            '{' => '}',
            '[' => ']',
            '(' => ')',
        ];

        $namedBlocks = [
            'if'      => ['elseif', 'endif'],
            'elseif'  => ['endif'],
            'foreach' => ['endforeach'],
            'for'     => ['endfor'],
            'switch'  => ['endswitch'],
            'while'   => ['endwhile'],
            'case'    => ['break', 'return', 'throw', 'case']
        ];

        $lines = [];
        foreach (explode("\n", $code) as $line) {
            $line = trim($line);
            if (empty($line)) {
                $lines[] = "";
                continue;
            }

            $correctionLevel = 0;

            if ($stack->count() > 0) {
                $currentBlockStart = $stack->top();
                $bracedBlockEnd = $bracedBlocks[$currentBlockStart] ?? null;

                if ($bracedBlockEnd && str_starts_with($line, $bracedBlockEnd)) {
                    $stack->pop();
                }

                foreach ($namedBlocks[$currentBlockStart] ?? [] as $namedBlockEnd) {
                    if (str_starts_with($line, $namedBlockEnd)) {
                        if ($currentBlockStart === 'case' && $namedBlockEnd !== 'case') {
                            $correctionLevel = 1;
                        }
                        $stack->pop();
                    }
                }
            }

            $level = $baseLevel + $stack->count() + $correctionLevel;

            if (str_ends_with($line, ':')) {
                foreach (array_keys($namedBlocks) as $namedBlockStart) {
                    if (str_starts_with($line, $namedBlockStart)) {
                        $stack->push($namedBlockStart);
                        break;
                    }
                }
            } else {
                foreach (array_keys($bracedBlocks) as $bracedBlockStart) {
                    if (str_ends_with($line, $bracedBlockStart)) {
                        $stack->push($bracedBlockStart);
                        break;
                    }
                }
            }

            $line = str_repeat(self::INDENTATION, $level) . $line;
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
