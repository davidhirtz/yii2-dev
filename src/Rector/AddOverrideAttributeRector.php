<?php

declare(strict_types=1);

namespace Hirtz\Dev\Rector;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Rector\PhpParser\AstResolver;
use Rector\Rector\AbstractRector;
use Rector\RuleDoc\CodeSample\CodeSample;
use Rector\RuleDoc\RuleDefinition;
use Rector\ValueObject\MethodName;

/**
 * Rector's deprecated `AddOverrideAttributeToOverriddenMethodsRector` by default, minus its exemptions of `setUp()`,
 * `tearDown()` and anonymous classes: an interface method and a parent's abstract or empty one (no statement, a lone
 * `return null;` or `throw`) stay unattributed.
 */
final class AddOverrideAttributeRector extends AbstractRector
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly AstResolver $astResolver,
        private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add #[Override] to a method overriding a parent class method', [
            new CodeSample(
                'class Child extends Base { public function init() {} }',
                'class Child extends Base { #[\Override] public function init() {} }',
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $className = $this->getName($node);

        if ($className === null || !$node->extends || !$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $parents = $this->reflectionProvider->getClass($className)->getParents();
        $hasChanged = false;

        foreach ($node->getMethods() as $method) {
            if ($this->shouldAddAttribute($method, $parents)) {
                $method->attrGroups[] = new AttributeGroup([new Attribute(new FullyQualified('Override'))]);
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
    }

    /**
     * @param list<ClassReflection> $parents
     */
    private function shouldAddAttribute(ClassMethod $method, array $parents): bool
    {
        $name = $method->name->toString();

        if (
            $method->isPrivate()
            || $this->isName($method, MethodName::CONSTRUCT)
            || $this->phpAttributeAnalyzer->hasPhpAttribute($method, 'Override')
        ) {
            return false;
        }

        foreach ($parents as $parent) {
            if (!$parent->hasNativeMethod($name)) {
                continue;
            }

            if ($parent->getNativeMethod($name)->isPrivate()) {
                return false;
            }

            $parentMethod = $this->findClassMethod($parent, $name);

            if ($parentMethod !== null && !$this->isMeantToBeImplemented($parentMethod)) {
                return true;
            }
        }

        return false;
    }

    private function findClassMethod(ClassReflection $class, string $name): ?ClassMethod
    {
        $classLike = $this->astResolver->resolveClassFromClassReflection($class);
        $method = $classLike?->getMethod($name);

        if ($method !== null || $classLike === null) {
            return $method;
        }

        foreach ($classLike->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $method = $this->astResolver->resolveClassFromName($trait->toString())?->getMethod($name);

                if ($method !== null) {
                    return $method;
                }
            }
        }

        return null;
    }

    private function isMeantToBeImplemented(ClassMethod $method): bool
    {
        if ($method->isAbstract() || !$method->stmts) {
            return true;
        }

        if (count($method->stmts) !== 1) {
            return false;
        }

        $stmt = $method->stmts[0];

        return ($stmt instanceof Return_ && $stmt->expr instanceof ConstFetch && $this->isName($stmt->expr, 'null'))
            || ($stmt instanceof Expression && $stmt->expr instanceof Throw_);
    }
}
