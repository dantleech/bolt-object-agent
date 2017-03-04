<?php

namespace Psi\Bridge\ObjectAgent\Bolt;

use Doctrine\ORM\Query\Expr;
use Psi\Component\ObjectAgent\Query\Comparison;
use Psi\Component\ObjectAgent\Query\Composite;
use Psi\Component\ObjectAgent\Query\Expression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;

class ExpressionVisitor
{
    private $queryBuilder;
    private $sourceAlias;

    private $parameters = [];

    public function __construct(ExpressionBuilder $queryBuilder, string $sourceAlias)
    {
        $this->queryBuilder = $queryBuilder;
        $this->sourceAlias = $sourceAlias;
    }

    /**
     * Walk the given expression to build up the ORM query builder.
     */
    public function dispatch(Expression $expr)
    {
        switch (true) {
            case $expr instanceof Comparison:
                return $this->walkComparison($expr);
                break;

            case $expr instanceof Composite:
                return $this->walkComposite($expr);
        }

        throw new \RuntimeException('Unknown Expression: ' . get_class($expr));
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    private function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        $value = $comparison->getValue();

        switch ($comparison->getComparator()) {
            case Comparison::EQUALS:
                return $this->queryBuilder->eq($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::NOT_EQUALS:
                return $this->queryBuilder->neq($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::LESS_THAN:
                return $this->queryBuilder->lt($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::LESS_THAN_EQUAL:
                return $this->queryBuilder->lte($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::GREATER_THAN:
                return $this->queryBuilder->gt($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::GREATER_THAN_EQUAL:
                return $this->queryBuilder->gte($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::IN:
                $expr = $this->queryBuilder;
                return $this->queryBuilder->in($this->getField($field), array_map(function ($value) use ($expr) {
                    return $expr->literal($value);
                }, $value));

            case Comparison::NOT_IN:
                return $this->queryBuilder->notIn($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::CONTAINS:
                return $this->queryBuilder->like($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::NOT_CONTAINS:
                return $this->queryBuilder->notLike($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::NULL:
                return $this->queryBuilder->isNull($this->getField($field), $this->registerParameter($field, $value));

            case Comparison::NOT_NULL:
                return $this->queryBuilder->isNotNull($this->getField($field), $this->registerParameter($field, $value));
        }

        throw new \RuntimeException('Unknown comparator: ' . $comparison->getComparator());
    }

    private function walkComposite(Composite $expression)
    {
        $expressions = $expression->getExpressions();

        if (empty($expressions)) {
            return;
        }

        $ormExpressions = [];
        foreach ($expressions as $index => $childExpression) {
            $ormExpressions[] = $this->dispatch($childExpression);
        }

        $method = $expression->getType() == Composite::AND ? 'andX' : 'orX';

        return call_user_func_array([$this->queryBuilder, $method], $ormExpressions);
    }

    private function getField($field): string
    {
        if (false !== strpos($field, '.')) {
            return $field;
        }
        return $this->sourceAlias . '.' . $field;
    }

    private function registerParameter(string $name, $value)
    {
        $name = str_replace('.', '_', $name);
        $this->parameters[$name] = $value;

        return ':' . $name;
    }
}
