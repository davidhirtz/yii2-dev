<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

// The shared Rector setup, used the same way as `php-cs-fixer.php` above it:
//
//     $config = require __DIR__ . '/vendor/davidhirtz/yii2-dev/config/rector.php';
//
//     return $config([
//         __DIR__ . '/app',
//         __DIR__ . '/tests',
//     ]);

return static function (array $paths): RectorConfigBuilder {
    return RectorConfig::configure()
        ->withPhpSets(php83: true)
        ->withRules([
            AddOverrideAttributeToOverriddenMethodsRector::class,
            AddParamTypeFromPropertyTypeRector::class,
            AddPropertyTypeDeclarationRector::class,
            AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
            AddReturnTypeDeclarationRector::class,
            AddVoidReturnTypeWhereNoReturnRector::class,
            CallUserFuncArrayToVariadicRector::class,
            DeclareStrictTypesRector::class,
            InlineConstructorDefaultToPropertyRector::class,
            ReturnTypeFromStrictNativeCallRector::class,
            StringClassNameToClassConstantRector::class,
            TypedPropertyFromAssignsRector::class,
        ])
        ->withPaths($paths);
};
