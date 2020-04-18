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
namespace Hyperf\Autoload;

use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Di\Annotation\AnnotationInterface;
use Hyperf\Di\BetterReflectionManager;
use Hyperf\Di\MetadataCollector;
use Hyperf\Utils\Str;
use Roave\BetterReflection\Reflection\ReflectionClass;

class Scanner
{
    /**
     * @var ClassLoader
     */
    protected $classloader;

    protected $path = BASE_PATH . '/runtime/container/collectors.cache';

    public function __construct(ClassLoader $classloader, ScanConfig $scanConfig)
    {
        $this->classloader = $classloader;

        foreach ($scanConfig->getIgnoreAnnotations() as $annotation) {
            AnnotationReader::addGlobalIgnoredName($annotation);
        }
        foreach ($scanConfig->getGlobalImports() as $alias => $annotation) {
            AnnotationReader::addGlobalImports($alias, $annotation);
        }
    }

    public function collect(AnnotationReader $reader, ReflectionClass $reflection)
    {
        $className = $reflection->getName();
        // Parse class annotations
        $classAnnotations = $reader->getClassAnnotations($reflection);
        if (! empty($classAnnotations)) {
            foreach ($classAnnotations as $classAnnotation) {
                if ($classAnnotation instanceof AnnotationInterface) {
                    $classAnnotation->collectClass($className);
                }
            }
        }
        // Parse properties annotations
        $properties = $reflection->getImmediateProperties();
        foreach ($properties as $property) {
            $propertyAnnotations = $reader->getPropertyAnnotations($property);
            if (! empty($propertyAnnotations)) {
                foreach ($propertyAnnotations as $propertyAnnotation) {
                    if ($propertyAnnotation instanceof AnnotationInterface) {
                        $propertyAnnotation->collectProperty($className, $property->getName());
                    }
                }
            }
        }
        // Parse methods annotations
        $methods = $reflection->getImmediateMethods();
        foreach ($methods as $method) {
            $methodAnnotations = $reader->getMethodAnnotations($method);
            if (! empty($methodAnnotations)) {
                foreach ($methodAnnotations as $methodAnnotation) {
                    if ($methodAnnotation instanceof AnnotationInterface) {
                        $methodAnnotation->collectMethod($className, $method->getName());
                    }
                }
            }
        }

        unset($reflection, $classAnnotations, $properties, $methods, $parentClassNames, $traitNames);
    }

    public function scan(array $paths = [], array $shouldCache = [], array $collectors = []): array
    {
        $classes = [];
        if (! $paths) {
            return $classes;
        }
        $paths = $this->normalizeDir($paths);

        $reflector = BetterReflectionManager::initClassReflector($paths);
        $classes = $reflector->getAllClasses();

        $annotationReader = new AnnotationReader();
        $cached = $this->deserializeCachedCollectors($collectors);
        if (! $cached) {
            foreach ($classes as $reflectionClass) {
                if (Str::startsWith($reflectionClass->getName(), $shouldCache)) {
                    $this->collect($annotationReader, $reflectionClass);
                }
            }

            $data = [];
            /** @var MetadataCollector $collector */
            foreach ($collectors as $collector) {
                $data[$collector] = $collector::serialize();
            }

            if ($data) {
                @mkdir(dirname($this->path), 0777, true);
                file_put_contents($this->path, serialize($data));
            }
        }

        foreach ($classes as $reflectionClass) {
            if (Str::startsWith($reflectionClass->getName(), $shouldCache)) {
                continue;
            }

            $this->collect($annotationReader, $reflectionClass);
        }

        unset($annotationReader);

        return $classes;
    }

    /**
     * Normalizes given directory names by removing directory not exist.
     */
    public function normalizeDir(array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $result[] = $path;
            }
        }

        return $result;
    }

    protected function deserializeCachedCollectors(array $collectors): bool
    {
        if (! file_exists($this->path)) {
            return false;
        }

        $data = unserialize(file_get_contents($this->path));
        foreach ($data as $collector => $deserialized) {
            /** @var MetadataCollector $collector */
            if (in_array($collector, $collectors)) {
                $collector::deserialize($deserialized);
            }
        }

        return true;
    }
}
