<?php

namespace Salim\FilterResolver;

use Illuminate\Database\Eloquent\Builder;

class EloquentFilterResolver
{
    private static string $databaseDriverName;

    public static function resolve(Builder &$query, string $filterString): Builder
    {
        $tokens = self::getTokens($filterString);
        $index = 0;

        self::setDatabaseDriverName($query);

        return self::resolveFilter($query, $tokens, $index);
    }

    private static function getTokens(string $filterString): array
    {
        $tokens = [];
        $token = '';
        $inString = false;

        for ($i = 0; $i < strlen($filterString); $i++) {
            $c = $filterString[$i];

            if ($c === '"') {
                $inString = !$inString;
                if (!$inString) {
                    $tokens[] = $token;
                    $token = '';
                }
            } else if ($inString) {
                if ($c === '\\' && $i + 1 <= strlen($filterString) && $filterString[$i + 1] === '"') {
                    $token .= '"';
                    $i++;
                } else {
                    $token .= $c;
                }
            } else if ($c === '(' || $c === ')' || $c === ' ' || $c === ',') {
                if ($token !== '') {
                    $tokens[] = $token;
                }
                $token = '';

                if ($c === '(' || $c === ')') {
                    $tokens[] = $c;
                }
            } else {
                $token .= $c;
            }
        }

        if ($token !== '') {
            $tokens[] = $token;
        }

        return $tokens;
    }

    private static function parseQuery(Builder &$query, array $tokens, int &$index, $operand = 'AND'): void
    {
        $condition = self::getCondition($tokens[$index]);
        $index += 2; // Skip opening '('
        $field = $tokens[$index];
        $index++;
        $value = $tokens[$index];
        $index += 2; // Skip closing ')'

        $value = match ($value) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => trim($value, '"'),
        };

        if ($condition === ('pgsql' === self::$databaseDriverName ? 'ILIKE' : 'LIKE')) {
            $value = "%{$value}%";
        } else if ($condition === 'IN' || $condition === 'NOT IN') {
            $value = explode('|', $value);
        }

        $lastDot = strrpos($field, '.');

        if ($lastDot !== false) {
            $relation = substr($field, 0, $lastDot);
            $field = substr($field, $lastDot + 1);
            self::buildRelationQuery($query, $operand, $condition, $relation, $field, $value);
        } else {
            self::buildQuery($query, $operand, $condition, $field, $value);
        }
    }

    private static function buildRelationQuery(Builder &$query, $operand, $condition, $relation, $field, $value): void
    {
        $relationMethod = 'whereHas';
        if ($operand === 'OR') $relationMethod = 'orWhereHas';

        $query->$relationMethod($relation, fn($q) => self::buildQuery($q, 'AND', $condition, $field, $value));
    }

    private static function buildQuery(Builder &$query, $operand, $condition, $field, $value): void
    {
        $queryMethod = 'where';
        if ($operand === 'OR') $queryMethod = 'orWhere';

        if ($condition === 'IN') $queryMethod .= 'In';
        else if ($condition === 'NOT IN') $queryMethod .= 'NotIn';

        if ($condition === 'IN' || $condition === 'NOT IN') {
            $query->$queryMethod($field, $value);
        } else {
            $query->$queryMethod($field, $condition, $value);
        }
    }

    private static function resolveFilter(Builder &$query, array $tokens, int &$index): Builder
    {

        while ($index < count($tokens)) {
            $token = $tokens[$index];

            if ($token === ')') {
                break;
            }

            $operand = 'AND';

            $upperToken = strtoupper($token);
            if ($upperToken === 'AND' || $upperToken === 'OR') {
                $operand = $upperToken;
                $index++;
                $token = $tokens[$index];
            }

            if ($token === '(') {
                $index++;
                if ($operand === 'AND') {
                    $query->where(function ($q) use ($tokens, &$index) {
                        self::resolveFilter($q, $tokens, $index);
                    });
                } else {
                    $query->orWhere(function ($q) use ($tokens, &$index) {
                        self::resolveFilter($q, $tokens, $index);
                    });
                }
                $index++; // Skip the closing ')'
                continue;
            }

            self::parseQuery($query, $tokens, $index, $operand);
        }

        return $query;
    }

    private static function getCondition(string $condition): string
    {

        $conditions = [
            'eq' => '=',
            'ne' => '!=',
            'gt' => '>',
            'lt' => '<',
            'gte' => '>=',
            'lte' => '<=',
            'contains' => 'pgsql' === self::$databaseDriverName ? 'ILIKE' : 'LIKE',
            'in' => 'IN',
            'notIn' => 'NOT IN',
        ];

        if (array_key_exists($condition, $conditions)) {
            return $conditions[$condition];
        }

        throw new \InvalidArgumentException("Invalid condition: {$condition}");
    }

    private static function setDatabaseDriverName(Builder $builder): void
    {
        self::$databaseDriverName =  $builder->getModel()->getConnection()->getDriverName();
    }
}


