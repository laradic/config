<?php


namespace Laradic\Config;


class ExpressionLanguage extends \Symfony\Component\ExpressionLanguage\ExpressionLanguage
{
    public function evaluate($expression, $values = [])
    {
        return $this->parse($expression, array_keys($values))->getNodes()->evaluate($this->functions, new GetterObjectDecorator($values));
    }
}