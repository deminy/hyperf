<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Di\LazyLoader;

use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;

class ClassLazyProxyBuilder extends AbstractLazyProxyBuilder
{
    public function addClassRelationship(): AbstractLazyProxyBuilder
    {
        if (strpos($this->originalClassName, '\\') !== 0) {
            $originalClassName = '\\'.$this->originalClassName;
        } else {
            $originalClassName = $this->originalClassName;
        }
        $this->builder = $this->builder->extend($originalClassName);
        return $this;
    }
}
