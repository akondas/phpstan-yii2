<?php

declare(strict_types=1);

namespace Proget\PHPStan\Yii2;

use PhpParser\Node;

final class ServiceMap
{
    /**
     * @var string[]
     */
    private $services = [];

    public function __construct(string $configPath)
    {
        if (!file_exists($configPath)) {
            throw new \InvalidArgumentException(sprintf('Provided config path %s must exist', $configPath));
        }

        \defined('YII_ENV_DEV') or \define('YII_ENV_DEV', false);
        \defined('YII_ENV_PROD') or \define('YII_ENV_PROD', false);
        \defined('YII_ENV_TEST') or \define('YII_ENV_TEST', true);

        $config = require $configPath;
        foreach ($config['container']['singletons'] as $id => $service) {
            if ($service instanceof \Closure || \is_string($service)) {
                $returnType = (new \ReflectionFunction($service))->getReturnType();
                if (!$returnType instanceof \ReflectionType) {
                    throw new \RuntimeException(sprintf('Please provide return type for %s service closure', $id));
                }

                $this->services[$id] = $returnType->getName();
            } else {
                $this->services[$id] = $service['class'] ?? $service[0]['class'];
            }
        }
    }

    public function getServiceClassFromNode(Node $node): ?string
    {
        if ($node instanceof Node\Scalar\String_ && isset($this->services[$node->value])) {
            return $this->services[$node->value];
        }

        return null;
    }
}
