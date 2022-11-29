<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use App\Helpers\FileAccessParameters;

class FileserverController extends Controller
{

    /**
     * Выбор метода для обработки get запроса
     * @param Request $request
     *
     * @return mixed
     */
    public function getMethods(Request $request)
    {
        $fileAccessParameters =  FileAccessParameters::fromRequest($request);
        if (!pathinfo($fileAccessParameters -> path, PATHINFO_EXTENSION)) {
            return $this -> listUserFiles($fileAccessParameters -> path, $fileAccessParameters -> localDisk);
        } else {
            return $this -> downloadFile($fileAccessParameters -> path, $fileAccessParameters -> localDisk);
        }
    }
    

    /**
     * Вывод списка файлов и подпапок с файлами
     * @param string $requestFolder
     * @param Filesystem $localDisk
     *
     * @return JsonResponse
     */
    protected function listUserFiles(string $requestFolder, Filesystem $localDisk): JsonResponse
    {
       
        if (!$localDisk -> exists($requestFolder)) {
            return response()->json([
                'error' => 'Folder not found'
            ], 404);
        }
        $folders = $localDisk -> directories($requestFolder);
        $fileSystem = [];
        $fileSystem = [
                'size' => 0,
                'folders' => [],
                'files' => []];

        $files = $localDisk -> files($requestFolder);
        foreach ($files as $file) {
            $fileSystem['size'] += $localDisk->size($file);
            $fileInfo = ['name' => basename($file),
                        'size' => $localDisk->size($file),
            ];
            array_push($fileSystem['files'], $fileInfo);
        }
        foreach ($folders as $folder) {
            $folderInfo = ['name' => basename($folder),
                'size' => 0,
                'files' => []];
            $files = $localDisk -> files($folder);
            foreach ($files as $file) {
                $fileSystem['size'] += $localDisk->size($file);
                $folderInfo['size'] += $localDisk->size($file);
                $fileInfo = ['name' => basename($file),
                            'size' => $localDisk->size($file),
                ];
                array_push($folderInfo['files'], $fileInfo);
            }
            array_push($fileSystem['folders'], $folderInfo);
        }
        return response()->json([
            'filesystem' => $fileSystem
        ]);
    }

    /**
     * Скачивание файла с сервера
     * @param string $path
     * @param mixed $localDisk
     *
     * @return mixed
     */
    protected function downloadFile(string $path, $localDisk): mixed
    {
        if (!$localDisk -> exists($path)) {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }
        if (pathinfo($path, PATHINFO_EXTENSION)) {
            return Storage::download($path);
        } else {
                return response()->json([
                    'error' => 'Folder downloading not allowed'
                ], 406);
        }
    }

    /**
     * Загрузка файла на сервер
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function uploadFile(Request $request): JsonResponse
    {
        $fileAccessParameters =  FileAccessParameters::fromRequest($request);
        if (!$fileAccessParameters -> localDisk -> exists($fileAccessParameters -> path)) {
            return response()->json([
                'error' => 'Folder not found'
            ], 404);
        }
        if (substr($fileAccessParameters -> path, -1) != '/') {
            $fileAccessParameters -> path = $fileAccessParameters -> path . '/';
        }
        $content = $request -> file('file');
        $fileSize = $content -> getSize();
        if ($fileSize > 20 * 1024 * 1024) {
            return response()->json([
                'error' => 'Maximum file size is 20MB'
            ], 413);
        }
        $userFiles = $this -> listUserFiles(strval($request -> user() -> id), $fileAccessParameters -> localDisk);
        $alreadyInUse = $userFiles->getData() -> filesystem -> size;
        if ($alreadyInUse + $fileSize > 100 * 1024 * 1024) {
            return response()->json([
                'error' => 'Maximum sapce allowed is 100MB'
            ], 413);
        }
        $finalPath = $fileAccessParameters -> path . $content -> getClientOriginalName();
        $stored_path = $content -> store($fileAccessParameters -> path);
        $fileAccessParameters -> localDisk -> move($stored_path, $finalPath);
        $finalPath = explode('/', $finalPath);
        unset($finalPath[0]);
        $finalPath = implode('/', $finalPath);
        return response()-> json([
            'path' => $finalPath
        ]);
    }

    /**
     * Удаление фала или папки
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteFileOrFolder(Request $request): JsonResponse
    {
        $fileAccessParameters =  FileAccessParameters::fromRequest($request);
        if ($fileAccessParameters -> localDisk -> exists($fileAccessParameters -> path)) {
            if (pathinfo($fileAccessParameters -> path, PATHINFO_EXTENSION)) {
                $fileAccessParameters -> localDisk -> delete($fileAccessParameters -> path);
            } else {
                $fileAccessParameters -> localDisk -> deleteDirectory($fileAccessParameters -> path);
            }
            return response()-> json([
                'result' => 'Deleted'
            ]);
        } else {
            return response()->json([
                'error' => 'Not found'
            ], 404);
        }
    }

    /**
     * Переименование файла
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function renameFile(Request $request): JsonResponse
    {
        $fileAccessParameters =  FileAccessParameters::fromRequest($request);
        if ($fileAccessParameters -> localDisk -> exists($fileAccessParameters -> path)) {
            if (pathinfo($fileAccessParameters -> path, PATHINFO_EXTENSION)) {
                $newPath = dirname($fileAccessParameters -> path) . '/' . $request -> newName;
                $fileAccessParameters -> localDisk -> move($fileAccessParameters -> path, $newPath);
            } else {
                return response()-> json([
                    'result' => 'Folder can not be renamed'
                ], 406);
            }
            return response()-> json([
                'result' => 'Changed file name'
            ]);
        } else {
            return response()->json([
                'error' => 'File not found'
            ], 404);
        }
    }
    /**
     * Создание папки
     * @param Request $request
     *
     * @return JsonResponse
     */
    protected function createFolder(Request $request): JsonResponse
    {
        $fileAccessParameters =  FileAccessParameters::fromRequest($request);
        if (substr_count($fileAccessParameters -> path, '/') > 1) {
            return response()->json([
                'error' => 'Only 1 subfolder allowed'
            ], 406);
        }
        if ($fileAccessParameters -> localDisk -> exists($fileAccessParameters -> path)) {
            return response()->json([
                'error' => 'Folder already exist'
            ], 406);
        }
        if (substr($fileAccessParameters -> path, -1) != '/') {
            $path = $fileAccessParameters -> path . '/';
        }
        $fileAccessParameters -> localDisk -> makeDirectory($path);
        $finalPath = explode('/', $path);
        unset($finalPath[0]);
        $finalPath = implode('/', $finalPath);
        return response()-> json([
            'path' => $finalPath
        ]);
    }

    /**
     * Выбор метода для обработки post запроса
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function postMethods(Request $request): JsonResponse
    {
        if ($request -> file('file')) {
            return $this -> uploadFile($request);
        } else {
            return $this -> createFolder($request);
        }
    }

    /**
     * генерация id для файла
     * @return string
     */
    protected function generateUid(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $length = 11;
        $uid = '';
        while ($length-- > 0) {
            $uid .= $chars[random_int(0, 61)];
        }
        return $uid;
    }
    /**
     * Генерация публичной ссылки для скачивания файла
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function generateUniqueURL(Request $request): JsonResponse
    {
        $fileAccessParameters =  FileAccessParameters::fromRequest($request);
        if (!pathinfo($fileAccessParameters -> path, PATHINFO_EXTENSION)) {
            return response()-> json([
                'result' => 'Only files can have short urls'
            ], 406);
        }
        if (!$fileAccessParameters -> localDisk -> exists($fileAccessParameters -> path)) {
            return response()-> json([
                'result' => 'File not found'
            ], 404);
        }
        //TODO добавить проверку на коллизии
        $uid = $this->generateUid();
        $publicPath = 'public/' . $uid . '.' . pathinfo($fileAccessParameters -> path, PATHINFO_EXTENSION);
        $fileAccessParameters -> localDisk -> copy($fileAccessParameters -> path, $publicPath);
        $url = Storage::url($publicPath);
        $url = $request->getScheme() . '://' . $request->getHost() . '/api' . $url;
        return response()-> json([
            'url' => $url
        ]);
    }

    /**
     * Скачивание файла по публичной ссылке
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadShorturl(Request $request)
    {
        $path = 'public/' . $request -> filepath;
        return Storage::download($path);
    }
}
