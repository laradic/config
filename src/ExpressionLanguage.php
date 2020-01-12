<?php


namespace Laradic\Config;


class ExpressionLanguage extends \Symfony\Component\ExpressionLanguage\ExpressionLanguage
{
    public function evaluate($expression, $values = [])
    {
        $parsedExpression = $this->parse($expression, array_keys($values));
        $nodes            = $parsedExpression->getNodes();
        $evaluated        = $nodes->evaluate($this->functions, new GetterObjectDecorator($values));
        return $evaluated;
    }
}