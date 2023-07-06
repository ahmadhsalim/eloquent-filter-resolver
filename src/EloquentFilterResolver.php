<?php

namespace Salim\FilterResolver;

use Illuminate\Database\Eloquent\Builder;

class EloquentFilterResolver
{
    public static function resolve(Builder &$query, string $filterString)
    {
        $tokens = getTokens($filterString);
        $index = 0;

        return resolveFilter($query, $tokens, $index);
    }
}

function getTokens(string $filterString): array
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

function parseQuery(Builder &$query, array $tokens, int &$index, $operand = 'AND') {
    $condition = getCondition($tokens[$index]);
    $index += 2; // Skip opening '('
    $field = $tokens[$index];
    $index++;
    $value = $tokens[$index];
    $index += 2; // Skip closing ')'

    switch ($value) {
        case 'true':
            $value = true;
            break;
        case 'false':
            $value = false;
            break;
        case 'null':
            $value = null;
            break;
        default:
            $value = trim($value, '"');
    }

    if ($condition === 'LIKE') {
        $value = "%{$value}%";
    } else if ($condition === 'IN' || $condition === 'NOT IN') {
        $value = explode('|', $value);
    }

    $lastDot = strrpos($field, '.');

    if ($lastDot !== false) {
        $relation = substr($field, 0, $lastDot);
        $field = substr($field, $lastDot + 1);
        buildRelationQuery($query, $operand, $condition, $relation, $field, $value);
    } else {
        buildQuery($query, $operand, $condition, $field, $value);
    }
}

function buildRelationQuery(Builder &$query, $operand, $condition, $relation, $field, $value) {
    $relationMethod = 'whereHas';
    if ($operand === 'OR') $relationMethod = 'orWhereHas';

    $query->$relationMethod($relation, fn ($q) => buildQuery($q, 'AND', $condition, $field, $value));
}

function buildQuery(Builder &$query, $operand, $condition, $field, $value) {
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

function resolveFilter(Builder &$query, array $tokens, int &$index)
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
                    resolveFilter($q, $tokens, $index);
                });
            } else {
                $query->orWhere(function ($q) use ($tokens, &$index) {
                    resolveFilter($q, $tokens, $index);
                });
            }
            $index++; // Skip the closing ')'
            continue;
        }

        parseQuery($query, $tokens, $index, $operand);
    }

    return $query;
}

function getCondition(string $contition): string
{

    $conditions = [
        'eq' => '=',
        'ne' => '!=',
        'gt' => '>',
        'lt' => '<',
        'gte' => '>=',
        'lte' => '<=',
        'contains' => 'LIKE',
        'in' => 'IN',
        'notIn' => 'NOT IN',
    ];

    if (array_key_exists($contition, $conditions)) {
        return $conditions[$contition];
    }

    throw new \InvalidArgumentException("Invalid condition: {$contition}");
}
