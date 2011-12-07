<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * @author     William Durand <william.durand1@gmail.com>
 * @package    propel.generator.behavior
 */
class TypehintableBehavior extends Behavior
{
    private $refFKs		= array();

	private $crossFKs	= array();

    public function objectFilter(&$script)
    {
        if (0 === count($this->getParameters())) {
            return $script;
        }

        foreach ($this->getTable()->getReferrers() as $refFK) {
            $this->refFKs[$refFK->getTable()->getName()] = $refFK->getRefPhpName() ?: $refFK->getTable()->getPhpName();
        }

		foreach ($this->getTable()->getCrossFks() as $fkList) {
			list($refFK, $crossFK) = $fkList;
            $this->crossFKs[$crossFK->getForeignTable()->getName()] = $crossFK->getRefPhpName() ?: $crossFK->getForeignTable()->getPhpName();
        }

        return $this->filter($script);
    }

    protected function getColumnSetter($columnName)
    {
        return 'set' . $this->getTable()->getColumn($columnName)->getPhpName();
    }

    protected function getColumnRefAdder($columnName)
    {
		return 'add' . $this->refFKs[$columnName];
    }

    protected function getColumnCrossAdder($columnName)
    {
		return 'add' . $this->crossFKs[$columnName];
    }

    protected function filter(&$script)
    {
        foreach ($this->getParameters() as $columnName => $typehint) {
			if ($this->getTable()->containsColumn($columnName)) {
				$funcName = $this->getColumnSetter($columnName);
				$this->filterFunction($funcName, $typehint, $script);
			} elseif (array_key_exists($columnName, $this->refFKs)) {
				$funcName = $this->getColumnRefAdder($columnName);
				$this->filterFunction($funcName, $typehint, $script);
			} elseif (array_key_exists($columnName, $this->crossFKs)) {
				$funcName = $this->getColumnCrossAdder($columnName);
				$this->filterFunction($funcName, $typehint, $script);
			}
		}
	}

	protected function filterFunction($functionName, $typehint, &$script)
	{
		$pattern     = sprintf('#function %s\(#', $functionName);
		$replacement = sprintf('function %s(%s ', $functionName, $this->fixTypehint($typehint));
		$script      = preg_replace($pattern, $replacement, $script);
	}

    private function fixTypehint($typehint)
    {
        if ('array' === $typehint || '\\' === substr($typehint, 0, 1)) {
            return $typehint;
        }

        return '\\' . $typehint;
    }
}
