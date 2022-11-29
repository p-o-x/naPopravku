<?php

namespace App\Helpers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileAccessParameters
{
    public string $path;
    public Filesystem $localDisk;

    /**
     *
     * @param Request $request
     *
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        $user = $request->user();
        $localDisk = Storage::disk('local');
        $directories = $localDisk -> Directories();
        if (!in_array($user -> id, $directories)) {
            $localDisk -> makeDirectory($user -> id);
        }
        $path = strval($request->user()->id) . '/' . $request->filepath;
        $fileAccessParameters = new FileAccessParameters();
        $fileAccessParameters -> path = $path;
        $fileAccessParameters -> localDisk = $localDisk;
        return $fileAccessParameters;
    }
}
