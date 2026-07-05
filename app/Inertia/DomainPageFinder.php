<?php

namespace App\Inertia;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;
use InvalidArgumentException;

class DomainPageFinder extends FileViewFinder
{
    /**
     * @param  list<string>  $extensions
     */
    public function __construct(Filesystem $files, array $extensions)
    {
        parent::__construct($files, [], $extensions);
    }

    public function find($name): string
    {
        $domainPaths = [
            'marketing' => resource_path('js/marketing/pages'),
            'app' => resource_path('js/app/pages'),
            'auth' => resource_path('js/auth/pages'),
        ];

        foreach ($domainPaths as $prefix => $path) {
            if (! str_starts_with($name, "{$prefix}/")) {
                continue;
            }

            $relative = substr($name, strlen($prefix) + 1);

            return (new FileViewFinder($this->files, [$path], $this->extensions))->find($relative);
        }

        throw new InvalidArgumentException("Inertia page [{$name}] not found.");
    }
}
