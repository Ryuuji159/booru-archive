<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class MediaUsage
{
    /**
     * @return array{bytes: int, files: int}
     */
    public function calculate(): array
    {
        $root = config('filesystems.disks.'.config('filesystems.media_disk', 'media').'.root');

        if (! is_string($root) || ! File::isDirectory($root)) {
            return [
                'bytes' => 0,
                'files' => 0,
            ];
        }

        $bytes = 0;
        $files = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $bytes += $file->getSize() ?: 0;
                $files++;
            }
        } catch (Throwable) {
            return [
                'bytes' => 0,
                'files' => 0,
            ];
        }

        return [
            'bytes' => $bytes,
            'files' => $files,
        ];
    }
}
