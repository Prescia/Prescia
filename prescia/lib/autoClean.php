<?php

/**
 * Compile the restricted autoclean DSL into SQL and bound parameters.
 *
 * Supported grammar: field <|<=|>|>=|=|!=|<> NOW() - INTERVAL integer
 * DAY|WEEK|MONTH|YEAR|HOUR|MINUTE
 */
final class CPresciaAutoClean
{
    /**
     * @return array{sql: string, types: string, params: array<int, mixed>}
     */
    public static function compile($definition, $module, $dbo)
    {
        if (!is_string($definition) || !is_object($module) || !is_object($dbo)) {
            throw new InvalidArgumentException('Invalid autoclean definition context');
        }

        $pattern = '/^\\s*([A-Za-z_][A-Za-z0-9_]*)\\s*(<=|>=|<>|!=|=|<|>)\\s*NOW\\(\\)\\s*-\\s*INTERVAL\\s+([1-9][0-9]*)\\s+(MINUTE|HOUR|DAY|WEEK|MONTH|YEAR)\\s*$/i';
        if (preg_match($pattern, $definition, $matches) !== 1) {
            throw new InvalidArgumentException('Unsupported autoclean expression');
        }

        $field = $matches[1];
        $operator = $matches[2];
        $amount = (int) $matches[3];
        $unit = strtoupper($matches[4]);

        if ($amount < 1 || $amount > 1000000 || !isset($module->fields[$field])) {
            throw new InvalidArgumentException('Autoclean field is not declared by the module');
        }

        return array(
            'sql' => $dbo->quoteIdentifier($field).' '.$operator.' NOW() - INTERVAL '.$amount.' '.$unit,
            'types' => '',
            'params' => array(),
        );
    }
}
