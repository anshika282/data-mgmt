<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserDataRequest;
use App\Jobs\SendEmailJob;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserDataController extends Controller
{
    //view all the user data:
    public function index()
    {
        $users = User::all();

        return response()->json(['message' => 'Successfull!',
            'data' => $users],
            200);
    }

    //upload and store the data
    public function store(UserDataRequest $request)
    {
        try {
            $user = Auth::user();

            if ($user && $user->role === 'ADMIN') {
                $path = $request->file('file')->store('uploads');
                $filePath = Storage::path($path);
                $this->parseAndSaveData($filePath);

                return response()->json(['message' => 'Data uploaded and emails will be sent.'], 200);
            } else {
                return response()->json(['message' => 'Unauthorised to do this action.Only admin allowed'], 403);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

    }

    public function parseAndSaveData($filePath)
    {
        $data = array_map('str_getcsv', file($filePath));
        $header = array_shift($data);

        \DB::beginTransaction();

        try {
            foreach ($data as $row) {
                $rowData = [
                    'name' => $row[0],
                    'email' => $row[1],
                    'username' => $row[2],
                    'address' => $row[3],
                    'role' => $row[4],
                ];

                $validator = Validator::make($rowData, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'username' => 'required|string|max:255|unique:users,username',
                    'address' => 'required|string|max:255',
                    'role' => 'required|in:USER,ADMIN',
                ]);

                if ($validator->fails()) {
                    \DB::rollBack();
                    throw new Exception('Validation failed for row '.($index + 2).': '.implode(', ', $validator->errors()->all()));
                }
                $user = User::create($rowData);
                SendEmailJob::dispatch($user);
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }

    }

    public function backupDatabase()
    {
        try {
            $user = Auth::user();

            if ($user && $user->role === 'ADMIN') {
                $fileName = 'backup_'.'.sql';
                if (! Storage::disk('backups')->exists('')) {
                    Storage::disk('backups')->makeDirectory('');
                }
                $storagePath = storage_path("app/backups/{$fileName}");
                $host = config('database.connections.mysql.host');
                $password = config('database.connections.mysql.password');
                $database = config('database.connections.mysql.database');
                $username = config('database.connections.mysql.username');
                $command = sprintf(
                    'mysqldump --user=%s --password=%s --host=%s %s > %s',
                    escapeshellarg($username),
                    $password,
                    $host,
                    $database,
                    $storagePath
                );
                $result = null;
                $output = [];
                exec($command, $output, $result);

                if ($result !== 0) {
                    \Log::error('Database backup failed. Output: '.implode("\n", $output));

                    return response()->json(['error' => 'Failed to create database backup.', 'reason' => $output], 500);
                }
                if (Storage::disk('backups')->exists($fileName)) {
                    return Storage::disk('backups')->download($fileName);
                } else {
                    return response()->json([
                        'error' => 'Backup file not found.',
                    ], 404);
                }
            } else {
                return response()->json(['message' => 'Unauthorised to do this action.Only admin allowed'], 403);
            }

        } catch (\Exception $Ex) {
            return response()->json(['error' => $Ex->getMessage()], 500);
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,txt|max:2048',
        ]);

        try {
            $user = Auth::user();

            if ($user && $user->role === 'ADMIN') {
                $file = $request->file('backup_file');
                if (! Storage::disk('backups')->exists('')) {
                    Storage::disk('backups')->makeDirectory('');
                }
                $fileName = 'backup_'.now()->format('Y_m_d_H_i_s').'.sql';
                $filePath = $file->storeAs('', $fileName, 'backups');
                $storagePath = storage_path('app/backups/'.$filePath);
                $host = config('database.connections.mysql.host');
                $password = config('database.connections.mysql.password');
                $database = config('database.connections.mysql.database');
                $username = config('database.connections.mysql.username');
                $command = sprintf(
                    'mysql --host=%s --user=%s --password=%s %s < %s',
                    escapeshellarg($host),
                    escapeshellarg($username),
                    escapeshellarg($password),
                    escapeshellarg($database),
                    escapeshellarg($storagePath)
                );
                $result = null;
                $output = [];
                exec($command, $output, $result);

                if ($result !== 0) {
                    return response()->json(['error' => 'Failed to create database backup.', 'reason' => $output], 500);
                }

                return response()->json([
                    'message' => 'Database has been successfully restored from the backup.',
                ], 200);
            } else {
                return response()->json(['message' => 'Unauthorised to do this action.Only admin allowed'], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while restoring the database: '.$e->getMessage(),
            ], 500);
        }
    }
}
